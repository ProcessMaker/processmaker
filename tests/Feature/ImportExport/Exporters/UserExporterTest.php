<?php

namespace Tests\Feature\ImportExport\Exporters;

use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Exporters\UserExporter;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserExporterTest extends TestCase
{
    use HelperTrait;
    use RequestHelper;

    public $withPermissions = true;

    public function test()
    {
        DB::beginTransaction();
        $user = User::factory()->create(['username' => 'testuser', 'email' => 'foo@bar.com']);
        $group = Group::factory()->create(['name' => 'test group']);
        $user->groups()->sync([$group->id]);

        $permissions = ['create-groups', 'create-scripts', 'edit-groups'];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
        $user->permissions()->sync($permissionIds);

        $payload = $this->export($user, UserExporter::class);
        DB::rollBack(); // Delete all created items since DB::beginTransaction
        $this->assertEquals(0, User::where('username', 'testuser')->count());
        $this->import($payload);

        $user = User::where('username', 'testuser')->firstOrFail();
        $this->assertEquals('test group', $user->groups->first()->name);
        $this->assertEquals($permissions, $user->permissions->pluck('name')->toArray());
    }
}
