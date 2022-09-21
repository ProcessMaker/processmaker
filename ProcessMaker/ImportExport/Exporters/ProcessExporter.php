<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\ProcessNotificationSetting;
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
        $screenIds = array_unique(array_merge(
            [
                $process->cancel_screen_id,
                $process->request_detail_screen_id,
            ],
            $this->manager->getDependenciesOfType(Screen::class, $process, [], false)
        ));
        $screens = Screen::findMany($screenIds);
        foreach ($screens as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }
    }

    public function import() : bool
    {
        $process = $this->model;

        $process->user_id = $this->getDependents('user')[0]->id;

        $this->associateCategories(ScreenCategory::class, 'screen_category_id');

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
}
