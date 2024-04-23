<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Screen;

class ProcessLaunchpadExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['process_id'];

    public $incrementStringSeparator = null;

    public function export(): void
    {
        $this->addDependent('user', $this->model->user, UserExporter::class);

        $properties = json_decode($this->model->properties, true);
        $launchScreen = Screen::where('uuid', $properties['screen_uuid'])->first();
        if ($launchScreen) {
            $this->addDependent('screen', $launchScreen, ScreenExporter::class);
        }
    }

    public function import(): bool
    {
        foreach ($this->getDependents('user') as $dependent) {
            $this->model->user_id = $dependent->model->id;
        }

        foreach ($this->getDependents('screen') as $dependent) {
            $properties = json_decode($this->model->properties, true);
            $properties['screen_uuid'] = $dependent->model->uuid;
            $this->model->properties = json_encode($properties);
        }

        return $this->model->save();
    }
}
