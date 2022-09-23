<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Collection;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Screen;

class ProcessExporter extends ExporterBase
{
    public ExportManager $manager;

    public function export() : void
    {
        $process = $this->model;

        $this->manager = resolve(ExportManager::class);

        $this->addDependent('user', $process->user, UserExporter::class);

        // Process Categories.
        $this->exportCategories();

        // Notification Settings.
        $this->addReference('notification_settings', $process->notification_settings->toArray());

        // Screens.
        foreach ($this->getScreens($process) as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }

        // Subprocesses.
        foreach ($this->getSubprocesses($process) as $subProcess) {
            $this->addDependent(DependentType::SUB_PROCESSES, $subProcess, self::class);
        }
    }

    public function import() : bool
    {
        $process = $this->model;

        $process->user_id = $this->getDependents('user')[0]->model->id;

        $this->associateCategories(ProcessCategory::class, 'process_category_id');

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

    private function getSubprocesses($process): Collection
    {
        $ids = [];
        $elements = $process->getDefinitions()->getElementsByTagName('callActivity');
        foreach ($elements as $element) {
            $calledElementValue = optional($element->getAttributeNode('calledElement'))->value;
            $values = explode('-', $calledElementValue);
            if (count($values) === 2) {
                $ids[] = $values[1];
            }
        }

        return Process::findMany($ids);
    }
}
