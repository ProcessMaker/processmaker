<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;

class ScriptExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->categories as $category) {
            $this->addDependent(DependentType::CATEGORIES, $category, ScriptCategoryExporter::class);
        }
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
