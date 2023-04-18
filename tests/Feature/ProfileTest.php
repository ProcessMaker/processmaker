<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RequestHelper;

    /**
     * Test to make sure the controller and route work with the view
     */
    public function testEditRoute(): void
    {
        Artisan::call('db:seed', ['class' => 'PermissionSeeder']);
        $user = User::factory()->create(['is_administrator' => false]);

        // Set the URL & permission to test.
        $url = route('profile.edit');
        $permission = 'edit-personal-profile';

        // User has no permissions, so this should return 403.
        $response = $this->actingAs($user)->get($url);
        $response->assertStatus(403);

        // Attach the permission to our user.
        $user->permissions()->attach(Permission::byName($permission)->id);
        $user->is_administrator = true;
        $user->save();
        $user->refresh();

        // Our user now has permissions, so this should return 200.
        $this->assertTrue($user->hasPermission('edit-personal-profile'));
        $response = $this->actingAs($user)->get($url);
        $response->assertStatus(200);
        $response->assertViewIs('profile.edit');
    }

    /**
     * Test to make sure the controller and route work with the view
     */
    public function testShowRoute(): void
    {
        $user_id = User::factory()->create()->id;
        // get the URL
        $response = $this->webCall('GET', '/profile/' . $user_id);

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('profile.show');
    }

    public function testEditProfileGroupPermission(): void
    {
        Artisan::call('db:seed', ['class' => 'PermissionSeeder']);
        $user = User::factory()->create(['is_administrator' => false]);
        $group = Group::factory()->create(['name' => 'Test Permissions']);

        // Assign our user to the group.
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_type' => User::class,
            'member_id' => $user->id,
        ]);

        // Set the URL & permission to test.
        $url = route('profile.edit');
        $permission = 'edit-personal-profile';

        // Our group has no permissions, so this should return 403.
        $response = $this->actingAs($user, 'web')->get($url);
        $response->assertStatus(403);

        // Attach the permission to our group.
        $group->permissions()->sync([Permission::byName($permission)->id]);
        $user->is_administrator = true;
        $user->save();
        $user->refresh();

        // Our group now has permission, so this should return 200.
        $this->assertTrue($user->hasPermission('edit-personal-profile'));
        $response = $this->actingAs($user, 'web')->call('GET', $url);
        $response->assertStatus(200);
    }
}
