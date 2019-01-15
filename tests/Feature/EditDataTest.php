<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\PermissionAssignment;
use ProcessMaker\Models\User;
use Tests\TestCase;
use \PermissionSeeder;

/**
 * Test edit data
 */
class EditDataTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();

        // Creates an admin user
        $this->admin = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => true,
        ]);

        // Creates an user
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'is_administrator' => false,
        ]);

        // Create a group
        $this->group = factory(Group::class)->create(['name' => 'group']);
        factory(GroupMember::class)->create([
            'member_id' => $this->user->id,
            'member_type' => User::class,
            'group_id' => $this->group->id,
        ]);
        
        // Seed the permissions
        (new PermissionSeeder)->run($this->admin);
    }

    /**
     * Assign the required permission to the user and group.
     *
     */
    private function assignPermissions()
    {
        $permission = Permission::byGuardName('requests.edit_data');
        // Assign to user
        factory(PermissionAssignment::class)->create([
            'assignable_type' => User::class,
            'assignable_id' => $this->user->id,
            'permission_id' => $permission->id,
        ]);
        // Assign to group
        factory(PermissionAssignment::class)->create([
            'assignable_type' => Group::class,
            'assignable_id' => $this->group->id,
            'permission_id' => $permission->id,
        ]);
        $this->user->refresh();
        $this->flushSession();
    }

    /**
     * Test edit data with permissions
     */
    public function testEditDataWithPermissions()
    {
        $this->assignPermissions();
        $this->assertTrue($this->user->hasPermission('requests.edit_data'));
    }

    /**
     * Test edit data without permissions
     */
    public function testEditDataWithoutPermissions()
    {
        $this->assertFalse($this->user->hasPermission('requests.edit_data'));
    }

    /**
     * Test edit data with admin user
     */
    public function testEditDataWithAdmin()
    {
        $this->assertTrue($this->admin->hasPermission('requests.edit_data'));
    }
}
