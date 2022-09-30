<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\ImportExport\SignalHelper;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Managers\SignalManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\SignalData;

class ProcessExporter extends ExporterBase
{
    public ExportManager $manager;

    public function export() : void
    {
        $process = $this->model;

        $this->manager = resolve(ExportManager::class);

        $this->addDependent('user', $process->user, UserExporter::class);

        // Process Categories.

        $this->exportSignals();

        $this->exportAssignments();

        // Notification Settings.
        $this->addReference('notification_settings', $process->notification_settings->toArray());

        // Screens.
        foreach ($this->getScreens($process) as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }

        $this->exportSubprocesses();
    }

    public function import() : bool
    {
        $process = $this->model;

        $process->user_id = $this->getDependents('user')[0]->model->id;

        $this->associateCategories(ProcessCategory::class, 'process_category_id');

        $this->importSignals();

        $this->importSubprocesses();

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

    private function getScreens($process): Collection
    {
        $ids = array_merge(
            [
                $process->cancel_screen_id,
                $process->request_detail_screen_id,
            ],
            $this->manager->getDependenciesOfType(Screen::class, $process, [], false)
        );

        return Screen::findMany($ids);
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
            Utils::setAttributeAtXPath($this->model, $dependent->meta, 'callActivity', 'calledElement');
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
            if (!$process) {
                eval(\Psy\sh());
            }
            if ($process->package_key !== null) {
                continue; // not a subprocess
            }

            $path = $element->getNodePath();
            $processesByPath[$path] = $process;
        }

        return $processesByPath;
    }

    private function exportSignals()
    {
        $globalSignalInfo = [];
        foreach (SignalHelper::processessReferencedBySignals($this->model) as $dependentInfo) {
            if ($dependentInfo['type'] === SignalHelper::TYPE_GLOBAL) {
                $globalSignalInfo[] = $dependentInfo['signalData'];
            } else {
                // Do not export dependent processes based on signals yet
            }
        }
        $this->addReference('global-signals', $globalSignalInfo);
    }

    private function importSignals()
    {
        foreach ($this->getReference('global-signals') as $signalData) {
            $existing = SignalManager::findSignal($signalData['id']);
            if (!$existing) {
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
    }
}
