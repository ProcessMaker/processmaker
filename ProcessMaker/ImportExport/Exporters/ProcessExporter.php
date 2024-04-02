<?php

namespace ProcessMaker\ImportExport\Exporters;

use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\Psudomodels\Signal;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
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

        if ($process->user) {
            $this->addDependent('user', $process->user, UserExporter::class);
        }

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
            $screen = Screen::find($process->cancel_screen_id);
            if ($screen) {
                $this->addDependent('cancel-screen', $screen, ScreenExporter::class);
            } else {
                Log::debug("Cancel ScreenId: {$process->cancel_screen_id} not exists");
            }
        }
        if ($process->request_detail_screen_id) {
            $screen = Screen::find($process->request_detail_screen_id);
            if ($screen) {
                $this->addDependent('request-detail-screen', $screen, ScreenExporter::class);
            } else {
                Log::debug("Request Detail ScreenId: {$process->request_detail_screen_id} not exists");
            }
        }

        $this->exportSubprocesses();

        $this->exportMedia();
    }

    public function import($existingAssetInDatabase = null, $importingFromTemplate = false) : bool
    {
        if ($existingAssetInDatabase) {
            $this->model = Process::where('id', $existingAssetInDatabase)->first();
        }
        $process = $this->model;

        foreach ($this->getDependents('user') as $dependent) {
            $process->user_id = $dependent->model->id;
        }

        foreach ($this->getDependents('manager') as $dependent) {
            $process->manager_id = $dependent->model->id;
        }

        // Avoid associating the category from the manifest with processes imported from templates.
        // Use the user-selected category instead.
        if (!$importingFromTemplate) {
            $this->associateCategories(ProcessCategory::class, 'process_category_id');
        }

        $this->importSignals();

        foreach ($this->getDependents('cancel-screen') as $dependent) {
            $process->cancel_screen_id = $dependent->model->id;
        }

        foreach ($this->getDependents('request-detail-screen') as $dependent) {
            $process->request_detail_screen_id = $dependent->model->id;
        }

        $this->importAssetsByMode();

        $process->save();

        $process->notification_settings()->delete();
        $notificationSettings = $this->getReference('notification_settings');
        if (!is_null($this->getReference('notification_settings'))) {
            foreach ($notificationSettings as $setting) {
                unset($setting['process_id']);
                $process->notification_settings()->create($setting);
            }
        }

        $this->importMedia();

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
        foreach ($this->getDependents(DependentType::SUB_PROCESSES, true) as $dependent) {
            $id = $dependent->model->id;
            if ($id) {
                Utils::setAttributeAtXPath($this->model, $dependent->meta, 'calledElement', 'ProcessId-' . $dependent->model->id);
                Utils::setPmConfigValueAtXPath($this->model, $dependent->meta, 'calledElement', 'ProcessId-' . $dependent->model->id);
                Utils::setPmConfigValueAtXPath($this->model, $dependent->meta, 'processId', $dependent->model->id);
            } else {
                Utils::setAttributeAtXPath($this->model, $dependent->meta, 'calledElement', '');
                Utils::setAttributeAtXPath($this->model, $dependent->meta, 'pm:config', '{}');
            }
        }
    }

    private function importAssignments()
    {
        $userAssignments = [];
        $groupAssignments = [];

        foreach ($this->getDependents(DependentType::USER_ASSIGNMENT, true) as $dependent) {
            if (!array_key_exists($dependent->meta['path'], $userAssignments)) {
                $userAssignments[$dependent->meta['path']] = [];
            }
            $userAssignments[$dependent->meta['path']] = [
                ...$userAssignments[$dependent->meta['path']],
                ...[$dependent->model->id],
            ];
        }

        foreach ($this->getDependents(DependentType::GROUP_ASSIGNMENT, true) as $dependent) {
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
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignedUsers', implode(',', array_filter($ids)));
        }

        foreach ($groupAssignments as $path => $ids) {
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignment', $dependent->meta['assignmentType']);
            Utils::setAttributeAtXPath($this->model, $path, 'pm:assignedGroups', implode(',', array_filter($ids)));
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
        // Remove discarded signals from process
        $signals = $this->getReference('signals');
        if (!is_null($signals)) {
            foreach ($signals as [$signalUuid, $signalId]) {
                if ($this->options->get('mode', $signalUuid) === 'discard') {
                    Signal::removeFromProcess($signalId, $this->model);
                }
            }
        }

        // Update signals if the ID changed (signal was copied)
        $xml = $this->model->getDefinitions(true);
        $xpath = new DOMXPath($xml);
        foreach ($this->getDependents('signal') as $dependent) {
            $oldSignalId = $dependent->meta;
            $newSignalId = $dependent->model->id;

            $signalEventDefinitions = $xpath->query('//bpmn:signalEventDefinition[@signalRef="' . $oldSignalId . '"]');
            foreach ($signalEventDefinitions as $signalEventDefinition) {
                $signalEventDefinition->setAttribute('signalRef', $newSignalId);
            }

            $signals = $xpath->query('//bpmn:signal[@id="' . $oldSignalId . '"]');
            foreach ($signals as $signalElement) {
                $signalElement->setAttribute('id', $newSignalId);
            }
        }

        $this->model->bpmn = $xml->saveXML();
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
            $allowInterstitial = $element->getAttribute('pm:allowInterstitial');

            if (is_numeric($screenId)) {
                $screen = Screen::find($screenId);
                if ($screen) {
                    $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class, $meta);
                } else {
                    Log::debug("ScreenId: {$screenId} not exists");
                }
            }

            // Let's check if interstitialScreen exist
            if (is_numeric($interstitialScreenId) && $allowInterstitial === 'true') {
                $interstitialScreen = Screen::find($interstitialScreenId);
                if ($interstitialScreen) {
                    $this->addDependent(DependentType::INTERSTITIAL_SCREEN, $interstitialScreen, ScreenExporter::class, $meta);
                } else {
                    Log::debug("Interstitial screenId: {$interstitialScreenId} not exists");
                }
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
                $script = Script::find($scriptId);
                if ($script) {
                    $this->addDependent(DependentType::SCRIPTS, $script, ScriptExporter::class, $meta);
                } else {
                    Log::debug("ScriptId: {$scriptId} not exists");
                }
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

    /**
     * Imports assets according to the `saveAssetsMode` option of the manifest.
     *
     * For non-template imports, `saveAllAssets` is the default `saveAssetsMode`.
     *
     * This function reads the `options` array to determine the `saveAssetsMode` value,
     * and imports the appropriate assets based on the value.
     */
    private function importAssetsByMode(): void
    {
        // Retrieve the `manifest` and `options` arrays
        $manifest = $this->manifest->toArray(true);
        $options = (array) $this->options;

        // Determine the `saveAssetsMode` value from the `options` array
        $saveAssetsMode = collect($options['options'])
            ->filter(function ($optionValue, $optionKey) use ($manifest) {
                return Arr::has($manifest, $optionKey);
            })
            ->map(function ($optionValue, $optionKey) {
                return Arr::get($optionValue, 'saveAssetsMode');
            })
            ->first();

        // Import the appropriate assets based on the `saveAssetsMode` value
        if ($saveAssetsMode === null || $saveAssetsMode === 'saveAllAssets') {
            $this->importScreens();
            $this->importScripts();
            $this->importSubprocesses();
            $this->importAssignments();
        }
    }

    /**
     * Export the media associated with the process.
     */
    public function exportMedia(): void
    {
        $media = $this->model->media->map(function ($media) {
            $filePath = $media->id . '/' . $media->file_name;
            $file = Storage::disk('public')->path($filePath);
            $media->base64 = base64_encode(file_get_contents($file));

            return $media;
        });

        $this->addReference('media', $media->toArray());
    }

    /**
     * Imports media for the process.
     */
    public function importMedia(): void
    {
        $process = $this->model;

        $mediaArray = $this->getReference('media') ?? [];
        foreach ($mediaArray as $mediaElement) {
            $encodedFile = $mediaElement['base64'];
            unset($mediaElement['base64']);

            $process->addMediaFromBase64($encodedFile)
                ->usingFileName($mediaElement['file_name'])
                ->toMediaCollection($mediaElement['collection_name']);
        }
    }
}
