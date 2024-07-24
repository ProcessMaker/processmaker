<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\SavedSearch\ImportExport\SavedSearchExporter;
use ProcessMaker\Package\SavedSearch\Models\SavedSearch;

class ProcessLaunchpadExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['process_id'];

    public $incrementStringSeparator = null;

    public function export(): void
    {
        if ($this->model->user) {
            $this->addDependent('user', $this->model->user, UserExporter::class);
        }

        $properties = json_decode($this->model->properties, true);
        $screenUuid = $properties['screen_uuid'] ?? null;
        $launchScreen = Screen::where('uuid', $screenUuid)->first();
        if ($launchScreen) {
            $this->addDependent('screen', $launchScreen, ScreenExporter::class);
        }

        if (class_exists(SavedSearch::class)) {
            $properties = json_decode($this->model->properties, true);
            foreach (Arr::get($properties, 'tabs', []) as $tab) {
                if (isset($tab['idSavedSearch']) && $tab['idSavedSearch'] > 0) {
                    $savedSearch = SavedSearch::findOrFail($tab['idSavedSearch']);
                    $this->addDependent('savedSearch', $savedSearch, SavedSearchExporter::class, $tab['idSavedSearch']);
                }
            }
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

        if (class_exists(SavedSearch::class)) {
            $properties = json_decode($this->model->properties, true);
            foreach ($this->getDependents('savedSearch') as $dependent) {
                foreach (Arr::get($properties, 'tabs', []) as $key => $tab) {
                    if (isset($tab['idSavedSearch']) && $tab['idSavedSearch'] === $dependent->meta) {
                        Arr::set($properties, 'tabs.' . $key . '.idSavedSearch', $dependent->model->id);
                    }
                }
            }
            $this->model->properties = json_encode($properties);
        }

        return $this->model->save();
    }
}
