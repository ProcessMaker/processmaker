<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Group;

class GroupExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
