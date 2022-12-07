<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Permission;

class GroupWithUsersExporter extends GroupExporter
{
    public function export() : void
    {
        parent::export();
    }

    public function import() : bool
    {
        parent::import();
        $group = $this->model;

        return true;
    }
}
