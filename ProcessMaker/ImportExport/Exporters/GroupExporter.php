<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Permission;

class GroupExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public static $fallbackMatchColumn = 'name';

    public $discard = true;

    public function export() : void
    {
        // Skipping user expansion to avoid exporting entire group membership (can be tens of thousands).
        Log::info('[GroupExporter] Skipping user expansion', [
            'group_id' => $this->model->id,
        ]);
        $this->addReference('permissions', $this->model->permissions()->pluck('name')->toArray());
    }

    public function import() : bool
    {
        $group = $this->model;

        // Skipping user import for group membership. Can be tens of thousands of users.
        Log::info('[GroupExporter] Skipping user import for group', [
            'group_id' => $group->id,
        ]);

        $permissions = $this->getReference('permissions') ?? [];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        return true;
    }
}
