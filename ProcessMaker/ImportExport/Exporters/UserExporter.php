<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\User;

class UserExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
        return $this->model->save();
    }
}
