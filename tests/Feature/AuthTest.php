<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Permission;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tests to determine if we can manually log someone in by setting them in the Auth framework immediately
     *
     * @return void
     */
    public function testAuthLoginAndLogout()
    {
        $user = factory(User::class)->create();
        Auth::login($user);
        $this->assertEquals($user->id, Auth::id());
        Auth::logout();
        $this->assertNull(Auth::user());
    }

    /**
     * Tests the manual login functionality to support logging in with credentials passed from some external
     * source.
     */
    public function testAuthManualLogin()
    {
        // Build a user with a specified password
        $user = factory(User::class)->create([
            'username' =>'newuser',
            'password' => Hash::make('password')
        ]);
        // Make sure we have a failed attempt with a bad password
        $this->assertFalse(Auth::attempt([
            'username' => $user->username,
            'password' => 'invalidpassword'
        ]));
        // Test to see if we can provide a successful auth attempt
        $this->assertTrue(Auth::attempt([
            'username' => 'newuser',
            'password' => 'password'
        ]));
        $this->assertEquals($user->id, Auth::id());
    }

    /**
     * Tests the has-permission gate functionality to ensure functionality
     */
    public function testHasPermissionGate()
    {
        $user = factory(User::class)->create();
        // First, check with an invalid permission and test that it returns false
        $this->assertFalse($user->can('has-permission', 'invalid-perm'));
        // Now, let's add a Permission
        $permission = factory(Permission::class)->create([
            'code' => 'valid-test-perm'
        ]);

        // Now let's add a role
        $role = factory(Role::class)->create([
            'code' => 'test-role'
        ]);
        $user->role()->associate($role);
        $user->save();
        $role->permissions()->attach($permission);

        $this->assertTrue($user->can('has-permission', 'valid-test-perm'));
        // Test multiple permission checks
        $permission = factory(Permission::class)->create([
            'code' => 'another-valid-test-perm'
        ]);
        $role->permissions()->attach($permission);
        // Now there should be two permissions for the role
        $this->assertTrue($user->can('has-permission', 'valid-test-perm,another-valid-test-perm'));
        // Now test to see if we fail having an additional permission not set
        $this->assertFalse($user->can('has-permission', 'valid-test-perm,another-valid-test-perm,invalid-perm'));
        // Now test with spaces in the arguments
        $this->assertTrue($user->can('has-permission', 'valid-test-perm , another-valid-test-perm'));
    }
}
