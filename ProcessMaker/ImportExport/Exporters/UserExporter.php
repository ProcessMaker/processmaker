<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Permission;

class UserExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['username'];

    public static $fallbackMatchColumn = ['email', 'username'];

    public $discard = true;

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

    public static function doNotImport($uuid, $assetInfo)
    {
        $username = Arr::get($assetInfo, 'attributes.username');
        if ($username === 'admin' || $username === '_pm4_anon_user') {
            return true;
        }

        return false;
    }

    public function getName($model) : string
    {
        return $model->username ?? '';
    }
}
