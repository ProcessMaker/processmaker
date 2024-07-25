<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Permission;

class GroupExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public static $fallbackMatchColumn = 'name';

    public $discard = true;

    public function export() : void
    {
        foreach ($this->model->users as $dependentModel) {
            $this->addDependent(DependentType::USERS, $dependentModel, UserExporter::class);
        }

        $this->addReference('permissions', $this->model->permissions()->pluck('name')->toArray());
    }

    public function import() : bool
    {
        $group = $this->model;

        foreach ($this->getDependents('users') as $dependent) {
            $dependent->model->groups()->syncWithoutDetaching($group->id);
        }

        $permissions = $this->getReference('permissions') ?? [];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        return true;
    }
}
