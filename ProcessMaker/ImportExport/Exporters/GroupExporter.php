<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Permission;

class GroupExporter extends ExporterBase
{
    public function export() : void
    {
        $this->addReference('permissions', $this->model->permissions()->pluck('name')->toArray());
    }

    public function import() : bool
    {
        $group = $this->model;

        $permissions = $this->getReference('permissions');
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        return true;
    }
}
