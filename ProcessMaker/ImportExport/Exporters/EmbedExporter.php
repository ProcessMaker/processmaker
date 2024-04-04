<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Embed;

class EmbedExporter extends ExporterBase
{
    public static $fallbackMatchColumn = 'name';

    public $handleDuplicatesByIncrementing = ['name'];

    public function export(): void
    {
        $embed = $this->model;
        $this->addReference(DependentType::EMBED, $embed->toArray());
    }

    public function import(): bool
    {
        $embedElement = $this->getReference(DependentType::EMBED);
        $modelWithEmbed = $this->model->model_type::findOrFail($this->model->model_id);
        //
        return true;
    }
}