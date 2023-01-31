<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Psudomodels\Signal;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\SignalData;
use ProcessMaker\Models\User;

class ProcessExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public static $fallbackMatchColumn = 'name';

    public ExportManager $manager;

    public function export() : void
    {
        $process = $this->model;

        $this->manager = resolve(ExportManager::class);

        $this->addDependent('user', $process->user, UserExporter::class);

        if ($process->manager) {
            $this->addDependent('manager', $process->manager, UserExporter::class, null, ['properties']);
        }

        $this->exportScreens();

        $this->exportScripts();

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

        foreach ($this->getDependents('user') as $dependent) {
            $process->user_id = $dependent->model->id;
        }

        foreach ($this->getDependents('manager') as $dependent) {
            $process->manager_id = $dependent->model->id;
        }

        $this->associateCategories(ProcessCategory::class, 'process_category_id');

        $this->importSignals();

        foreach ($this->getDependents('cancel-screen') as $dependent) {
            $process->cancel_screen_id = $dependent->model->id;
        }

        foreach ($this->getDependents('request-detail-screen') as $dependent) {
            $process->request_detail_screen_id = $dependent->model->id;
        }

        $this->importScreens();

        $this->importScripts();

        $this->importSubprocesses();

        $this->importAssignments();

        $process->save();

        $process->notification_settings()->delete();
        foreach ($this->getReference('notification_settings') as $setting) {
            unset($setting['process_id']);
            $process->notification_settings()->create($setting);
        }

        return true;
    }

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
        $signals = [];
        foreach (Signal::inProcess($this->model) as $signal) {
            $dependent = $this->addDependent('signal', $signal, SignalExporter::class, $signal->id);

            // Keep track of signals. If the user decides to not import them later we need to know
            // which signals to remove from this process.
            $signals[] = [$dependent->uuid, $signal->id];

            if ($dependent->mode === 'discard') {
                $this->manifest->afterExport(function () use ($signal) {
                    Signal::removeFromProcess($signal->id, $this->model);
                });
            }
        }

        $this->addReference('signals', $signals);
    }

    private function importSignals()
    {
        foreach ($this->getReference('signals') as [$signalUuid, $signalId]) {
            if ($this->options->get('mode', $signalUuid) === 'discard') {
                Signal::removeFromProcess($signalId, $this->model);
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
            'bpmn:manualTask',
            'bpmn:startEvent',
            'bpmn:endEvent',
        ];

        foreach (Utils::getElementByMultipleTags($this->model->getDefinitions(true), $tags) as $element) {
            $path = $element->getNodePath();
            $meta = [
                'path' => $path,
            ];

            $screenId = $element->getAttribute('pm:screenRef');
            $interstitialScreenId = $element->getAttribute('pm:interstitialScreenRef');

            if (is_numeric($screenId)) {
                $screen = Screen::findOrFail($screenId);
                $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class, $meta);
            }

            // Let's check if interstitialScreen exist
            if (is_numeric($interstitialScreenId)) {
                $interstitialScreen = Screen::findOrFail($interstitialScreenId);
                $this->addDependent(DependentType::INTERSTITIAL_SCREEN, $interstitialScreen, ScreenExporter::class, $meta);
            }
        }
    }

    private function importScreens()
    {
        foreach ($this->getDependents(DependentType::SCREENS) as $dependent) {
            $path = $dependent->meta['path'];
            Utils::setAttributeAtXPath($this->model, $path, 'pm:screenRef', $dependent->model->id);
        }

        if ($this->getDependents(DependentType::INTERSTITIAL_SCREEN)) {
            foreach ($this->getDependents(DependentType::INTERSTITIAL_SCREEN) as $interDependent) {
                $path = $interDependent->meta['path'];
                Utils::setAttributeAtXPath($this->model, $path, 'pm:interstitialScreenRef', $interDependent->model->id);
            }
        }
    }

    private function exportScripts()
    {
        $tags = [
            'bpmn:scriptTask',
        ];

        foreach (Utils::getElementByMultipleTags($this->model->getDefinitions(true), $tags) as $element) {
            $path = $element->getNodePath();
            $meta = [
                'path' => $path,
            ];

            $scriptId = $element->getAttribute('pm:scriptRef');

            if (is_numeric($scriptId)) {
                $script = Script::findOrFail($scriptId);
                $this->addDependent(DependentType::SCRIPTS, $script, ScriptExporter::class, $meta);
            }
        }
    }

    private function importScripts()
    {
        foreach ($this->getDependents(DependentType::SCRIPTS) as $dependent) {
            $path = $dependent->meta['path'];
            Utils::setAttributeAtXPath($this->model, $path, 'pm:scriptRef', $dependent->model->id);
        }
    }

    public function discard(): bool
    {
        return true;
    }
}
