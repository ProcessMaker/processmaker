<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;

class EmbedExporter extends ExporterBase
{
    public function export(): void
    {
        $embed = $this->model;
        $this->addReference(DependentType::EMBED, $embed->toArray());
    }

    public function import(): bool
    {
        $this->model->save();

        return true;
    }
}
