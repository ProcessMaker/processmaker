<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\Models\Permission;

class GroupExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public static $fallbackMatchColumn = 'name';

    public function export() : void
    {
        if ($this->model->users->count() > 0) {
            foreach ($this->model->users as $user) {
                $this->addDependent('users', $user, UserExporter::class);
            }
        }
        $this->addReference('permissions', $this->model->permissions()->pluck('name')->toArray());
    }

    public function import() : bool
    {
        $group = $this->model;

        foreach ($this->getDependents('users') as $dependent) {
            $dependent->model->groups()->syncWithoutDetaching($group->id);
        }

        $permissions = $this->getReference('permissions');
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        return true;
    }

    public function discard() : bool
    {
        return true;
    }
}
