<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\GroupExporter;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class GroupExporterTest extends TestCase
{
    use HelperTrait;
    use RequestHelper;

    public $withPermissions = true;

    public function testGroupExporter()
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

    public function testGroupWithUsers()
    {
        DB::beginTransaction();

        // Creating 1 group and 10 users
        $group = Group::factory()->create(['name' => 'test group']);
        $users = User::factory(10)->create();

        // Attaching 5 users to the group and leave 5 without group
        foreach ($users as $user) {
            if ($user->id % 2 == 0) {
                $user->groups()->sync([$group->id]);
            }
        }
        // Adding permissions to the group
        $permissions = ['create-groups', 'create-scripts', 'edit-groups'];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $group->permissions()->sync($permissionIds);

        $this->runExportAndImport($group, GroupExporter::class, function () {
            DB::rollBack(); // Delete all created items since DB::beginTransaction
            $this->assertEquals(0, Group::get()->count());
            $this->assertEquals(0, GroupMember::get()->count());
        });

        $this->assertEquals(1, Group::where('name', 'test group')->count());
        $newGroup = Group::where('name', 'test group')->firstOrFail();
        $this->assertEquals($permissions, $newGroup->permissions->pluck('name')->toArray());
        $this->assertEquals(5, $newGroup->users->count());

        foreach ($newGroup->users as $newUser) {
            $groupMember = GroupMember::where(['member_id' => $newUser->id])->firstOrFail();
            $this->assertEquals($newUser->id, $groupMember->member_id);
        }
    }
}
