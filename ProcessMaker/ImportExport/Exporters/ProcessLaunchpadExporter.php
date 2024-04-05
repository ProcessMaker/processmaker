<?php

namespace ProcessMaker\ImportExport\Exporters;

class ProcessLaunchpadExporter extends ExporterBase
{
    public $discard = false;

    public $handleDuplicatesByIncrementing = ['process_id'];

    public $incrementStringSeparator = null;

    public function export(): void
    {
        $this->addDependent('user', $this->model->user, UserExporter::class);
    }

    public function import(): bool
    {
        foreach ($this->getDependents('user') as $dependent) {
            $this->model->user_id = $dependent->model->id;
        }

        return $this->model->save();
    }
}
