<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Http\Controllers\ProfileController;
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
        $user->invalidatePermissionCache(); // invalidate permission cache

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
        $user->invalidatePermissionCache();

        // Our group now has permission, so this should return 200.
        $this->assertTrue($user->hasPermission('edit-personal-profile'));
        $response = $this->actingAs($user, 'web')->call('GET', $url);
        $response->assertStatus(200);
    }

    public function testEditProfilePassesSignedInUserToSettingsAddons(): void
    {
        Artisan::call('db:seed', ['class' => 'PermissionSeeder']);
        $user = User::factory()->create([
            'is_administrator' => false,
            'delegation_user_id' => User::factory()->create()->id,
        ]);
        $user->permissions()->attach(Permission::byName('edit-personal-profile')->id);
        $user->refresh();
        $user->invalidatePermissionCache();

        $addons = new \ReflectionProperty(ProfileController::class, 'addons');
        $originalAddons = $addons->getValue();
        $captured = null;

        try {
            ProfileController::registerAddon([
                'method' => 'edit.settings',
                'data' => function (array $data) use (&$captured) {
                    $captured = $data;

                    return $data;
                },
            ]);

            $response = $this->actingAs($user)->get(route('profile.edit'));
        } finally {
            $addons->setValue(null, $originalAddons);
        }

        $response->assertStatus(200);
        $this->assertInstanceOf(User::class, $captured['user'] ?? null);
        $this->assertSame($user->id, $captured['user']->id);
        $this->assertSame($user->delegation_user_id, $captured['user']->delegation_user_id);
    }
}
