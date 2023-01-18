<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Permission;

class UserExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->groups as $group) {
            $this->addDependent(DependentType::GROUPS, $group, GroupExporter::class);
        }
        $this->addReference('permissions', $this->model->permissions()->pluck('name')->toArray());
    }

    public function import() : bool
    {
        $user = $this->model;
        $user->saveOrFail();

        foreach ($this->getDependents(DependentType::GROUPS) as $dependent) {
            $group = $dependent->model;
            $user->groups()->attach($group->id);
        }

        $permissions = $this->getReference('permissions');
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $user->permissions()->sync($permissionIds);

        return true;
    }

    public function getName(): string
    {
        return $this->model->username;
    }
}
