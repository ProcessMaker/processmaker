<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Embed;

class EmbedExporter extends ExporterBase
{
    public $discard = false;

    public function export(): void
    {
        $embed = $this->model;
        $this->addReference(DependentType::EMBED, $embed->toArray());
    }

    public function import(): bool
    {
        $this->model->value  = $this->getReference(DependentType::EMBED);
        $this->model->save();

        return true;
    }
}
