<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Managers\ExportManager;
use ProcessMaker\Models\Screen;

class ProcessExporter extends ExporterBase
{
    public ExportManager $manager;

    public function export() : void
    {
        $this->manager = resolve(ExportManager::class);

        // Process Categories.
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ProcessCategoryExporter::class);
        }

        // TODO Notification Settings.
        // foreach ($this->model->notification_settings as $notificationSetting) {
        //     $this->addDependent(DependentType::NOTIFICATION_SETTINGS, $notificationSetting, ProcessNotificationSettingExporter::class);
        // }

        // Screens.
        $screenIds = array_unique(array_merge(
            [
                $this->model->cancel_screen_id,
                $this->model->request_detail_screen_id,
            ],
            $this->manager->getDependenciesOfType(Screen::class, $this->model, [], false)
        ));
        $screens = Screen::findMany($screenIds);
        foreach ($screens as $screen) {
            $this->addDependent(DependentType::SCREENS, $screen, ScreenExporter::class);
        }
    }

    public function import() : bool
    {
        // Add user
        // Update screenRef
        // Associate Categories
        return $this->model->save();
    }
}
