<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\GroupExporter;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class GroupExporterTest extends TestCase
{
    use HelperTrait;
    use RequestHelper;

    public $withPermissions = true;

    public function test()
    {
        DB::beginTransaction();
        $group = Group::factory()->create(['name' => 'test group']);

        $permissions = ['create-groups', 'create-scripts', 'edit-groups'];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        $payload = $this->export($group, GroupExporter::class);
        DB::rollBack(); // Delete all created items since DB::beginTransaction
        $this->assertEquals(0, Group::where('name', 'test group')->count());
        $this->import($payload);

        $group = Group::where('name', 'test group')->firstOrFail();
        $this->assertEquals($permissions, $group->permissions->pluck('name')->toArray());
    }
}
