<?php

namespace ProcessMaker\ImportExport\Exporters;

class ProcessCategoryExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
