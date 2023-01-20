<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;

class UserExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['username'];

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

        foreach ($this->getDependents(DependentType::GROUPS) as $dependent) {
            $group = $dependent->model;
            $user->groups()->syncWithoutDetaching($group->id);
        }

        $permissions = $this->getReference('permissions');
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $user->permissions()->sync($permissionIds);

        return true;
    }

    /**
     * If it's the admin user or the anonymous user, don't match by UUID
     */
    public static function modelFinder($uuid, $assetInfo)
    {
        $key = Arr::get($assetInfo, 'attributes.username');
        if ($key === 'admin' || $key === '_pm4_anon_user') {
            return User::where('username', $key);
        }

        return parent::modelFinder($uuid, $assetInfo);
    }

    public static function doNotImport($uuid, $assetInfo)
    {
        $username = Arr::get($assetInfo, 'attributes.username');
        if ($username === 'admin' || $username === '_pm4_anon_user') {
            return true;
        }

        return false;
    }

    public function getName($model): string
    {
        return $model->username;
    }
}
