<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;

class ProcessExporter extends ExporterBase
{
    public ExportManager $manager;

    public function export() : void
    {
        $process = $this->model;

        $this->manager = resolve(ExportManager::class);

        $this->addDependent('user', $process->user, UserExporter::class);

        $this->exportScreens();

        $this->exportCategories();

        $this->exportSignals();

        $this->exportAssignments();

        // Notification Settings.
        $this->addReference('notification_settings', $process->notification_settings->toArray());

        // Screens
        if ($process->cancel_screen_id) {
            $screen = Screen::findOrFail($process->cancel_screen_id);
            $this->addDependent('cancel-screen', $screen, ScreenExporter::class);
        }
        if ($process->request_detail_screen_id) {
            $screen = Screen::findOrFail($process->request_detail_screen_id);
            $this->addDependent('request-detail-screen', $screen, ScreenExporter::class);
        }

        $this->exportSubprocesses();
    }

    public function import() : bool
    {
        $process = $this->model;

        $process->user_id = $this->getDependents('user')[0]->model->id;

        $this->associateCategories(ProcessCategory::class, 'process_category_id');

        $this->importSignals();

        foreach ($this->getDependents('cancel-screen') as $dependent) {
            $process->cancel_screen_id = $dependent->model->id;
        }

        foreach ($this->getDependents('request-detail-screen') as $dependent) {
            $process->request_detail_screen_id = $dependent->model->id;
        }

        $this->importScreens();

        $this->importSubprocesses();

        $this->importAssignments();

        // TODO
        // Update screenRef
        $process->save();

        $process->notification_settings()->delete();
        foreach ($this->getReference('notification_settings') as $setting) {
            unset($setting['process_id']);
            $process->notification_settings()->create($setting);
        }

        return true;
    }

    // private function getScreens($process): Collection
    // {
    //     $ids = array_merge(
    //         [
    //             $process->cancel_screen_id,
    //             $process->request_detail_screen_id,
    //         ],
    //         $this->manager->getDependenciesOfType(Screen::class, $process, [], false)
    //     );

    //     return Screen::findMany($ids);
    // }

    private function exportSubprocesses()
    {
        foreach ($this->getSubprocesses() as $path => $subProcess) {
            $this->addDependent(DependentType::SUB_PROCESSES, $subProcess, self::class, $path);
        }
    }

    private function importSubprocesses()
    {
        foreach ($this->getDependents(DependentType::SUB_PROCESSES) as $dependent) {
            Utils::setAttributeAtXPath($this->model, $dependent->meta, 'calledElement', 'ProcessId-' . $dependent->model->id);
            Utils::setPmConfigValueAtXPath($this->model, $dependent->meta, 'calledElement', 'ProcessId-' . $dependent->model->id);
            Utils::setPmConfigValueAtXPath($this->model, $dependent->meta, 'processId', $dependent->model->id);
        }
    }

    private function importAssignments()
    {
        $userAssignments = [];
        $groupAssignments = [];

        foreach ($this->getDependents(DependentType::USER_ASSIGNMENT) as $dependent) {
            if (!array_key_exists($dependent->meta['path'], $userAssignments)) {
                $userAssignments[$dependent->meta['path']] = [];
            }
            $userAssignments[$dependent->meta['path']] = [
                ...$userAssignments[$dependent->meta['path']],
                ...[$dependent->model->id],
            ];
        }

        foreach ($this->getDependents(DependentType::GROUP_ASSIGNMENT) as $dependent) {
            if (!array_key_exists($dependent->meta['path'], $groupAssignments)) {
                $groupAssignments[$dependent->meta['path']] = [];
            }
            $groupAssignments[$dependent->meta['path']] = [
                ...$groupAssignments[$dependent->meta['path']],
                ...[$dependent->model->id],
            ];
        }

        foreach ($userAssignments as $path => $ids) {
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignment', $dependent->meta['assignmentType']);
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignedUsers', implode(',', $ids));
        }

        foreach ($groupAssignments as $path => $ids) {
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignment', $dependent->meta['assignmentType']);
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignedGroups', implode(',', $ids));
        }
    }

    private function getSubprocesses(): array
    {
        $processesByPath = [];
        foreach ($this->model->getDefinitions(true)->getElementsByTagName('callActivity') as $element) {
            $calledElementValue = optional($element->getAttributeNode('calledElement'))->value;

            $values = explode('-', $calledElementValue);
            if (count($values) !== 2) {
                continue; // not a subprocess
            }

            $id = $values[1];
            if (!is_numeric($id)) {
                continue; // not a subprocess
            }

            $process = Process::find($values[1]);
            if (!$process || $process->package_key !== null) {
                continue; // not a subprocess
            }

            $path = $element->getNodePath();
            $processesByPath[$path] = $process;
        }

        return $processesByPath;
    }

    private function exportSignals()
    {
        $signalHelper = app()->make(SignalHelper::class);

        $globalSignalsInProcess = [];

        foreach ($signalHelper->processessReferencedByThrowSignals($this->model) as [$process, $signalId]) {
            $this->addDependent('signal-process', $process, self::class, $signalId);
        }

        foreach ($signalHelper->globalSignalsInProcess($this->model) as $signalInfo) {
            $globalSignalsInProcess[$signalInfo['id']] = $signalInfo;
        }
        $this->addReference('global-signals', $globalSignalsInProcess);
    }

    private function importSignals()
    {
        // Note: No associations are needed for process signals.
        // The dependent process has already been saved at this point.

        // Import global signals
        $signalHelper = app()->make(SignalHelper::class);
        $globalSignals = $signalHelper->getGlobalSignals();
        foreach ($this->getReference('global-signals') as $signalData) {
            if (!$globalSignals->has($signalData['id'])) {
                $signal = new SignalData($signalData['id'], $signalData['name'], $signalData['detail']);
                $errors = SignalManager::validateSignal($signal, null);
                if ($errors) {
                    throw new \Exception(json_encode($errors));
                }
                SignalManager::addSignal($signal);
            }
        }
    }

    private function exportAssignments()
    {
        $tags = [
            'bpmn:task',
            'bpmn:manualTask',
            'bpmn:callActivity',
        ];

        foreach (Utils::getAssignments($this->model, $tags) as $path => $assignments) {
            $meta = [
                'path' => $path,
                'assignmentType' => $assignments['assignmentType'],
            ];

            foreach ($assignments['userIds'] as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $this->addDependent(DependentType::USER_ASSIGNMENT, $user, UserExporter::class, $meta);
                }
            }

            foreach ($assignments['groupIds'] as $groupId) {
                $group = Group::find($groupId);
                if ($group) {
                    $this->addDependent(DependentType::GROUP_ASSIGNMENT, $group, GroupExporter::class, $meta);
                }
            }
        }
    }

    private function exportScreens()
    {
        $tags = [
            'bpmn:task',
        ];

        foreach (Utils::getElementByMultipleTags($this->model->getDefinitions(true), $tags) as $element) {
            $path = $element->getNodePath();
            $meta = [
                'path' => $path,
            ];

            $screenId = $element->getAttribute('pm:screenRef');
            $interstitialScreenId = $element->getAttribute('pm:interstitialScreenRef');

            if (!empty($screenId)) {
                $screen = Screen::findOrFail($screenId);
                $this->addDependent('task-screen-ref', $screen, ScreenExporter::class, $meta);
            }

            // Let's check if interstitialScreen exist
            if (!empty($interstitialScreenId)) {
                $interstitialScreen = Screen::findOrFail($interstitialScreenId);
                $this->addDependent('interstitial-screen-ref', $interstitialScreen, ScreenExporter::class, $meta);
            }
        }
    }

    private function importScreens()
    {
        foreach ($this->getDependents('task-screen-ref') as $dependent) {
            $path = $dependent->meta['path'];
            Utils::setAttributeAtXPath($this->model, $path, 'pm:screenRef', $dependent->model->id);
        }

        if ($this->getDependents('interstitial-screen-ref')) {
            foreach ($this->getDependents('interstitial-screen-ref') as $interDependent) {
                $path = $interDependent->meta['path'];
                Utils::setAttributeAtXPath($this->model, $path, 'pm:interstitialScreenRef', $interDependent->model->id);
            }
        }
    }
}
