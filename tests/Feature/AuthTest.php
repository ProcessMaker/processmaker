<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * Tests to determine if we can manually log someone in by setting them in the Auth framework immediately
     *
     * @return void
     */
    public function testAuthLoginAndLogout()
    {
        $user = User::factory()->create();
        Auth::login($user);
        $this->assertEquals($user->id, Auth::id());
        Auth::logoutCurrentDevice();
        $this->assertNull(Auth::user());
    }

    /**
     * Tests the manual login functionality to support logging in with credentials passed from some external
     * source.
     */
    public function testAuthManualLogin()
    {
        // Build a user with a specified password
        $user = User::factory()->create([
            'username' =>'newuser',
            'password' => Hash::make('password'),
        ]);
        // Make sure we have a failed attempt with a bad password
        $this->assertFalse(Auth::attempt([
            'username' => $user->username,
            'password' => 'invalidpassword',
        ]));
        // Test to see if we can provide a successful auth attempt
        $this->assertTrue(Auth::attempt([
            'username' => 'newuser',
            'password' => 'password',
        ]));
        $this->assertEquals($user->id, Auth::id());
    }

    public function testLoginWithUnknownUser()
    {
        $response = $this->post('login', [
            'username' => 'foobar',
            'password' => 'abc123',
        ]);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors();

        User::factory()->create([
            'username' => 'foobar',
            'password' => Hash::make('abc123'),
            'status' => 'ACTIVE',
        ]);

        $response = $this->post('login', [
            'username' => 'foobar',
            'password' => 'abc123',
        ]);

        // See https://github.com/ProcessMaker/processmaker/pull/4375
        $response->assertRedirect('/');

        $response->assertSessionHasNoErrors();
    }

    /**
     * 
     * Test logout flow
     */
    public function testLogoutStandard()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');
        // open logout url
        $response = $this->get('logout');

        // Verify it redirects to the login page
        $response->assertRedirect('/login');

        // Verify if the user is logged out
        $response = $this->get(route('home'));
        $response->assertRedirect('/login');
    }
}
