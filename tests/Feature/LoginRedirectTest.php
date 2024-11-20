<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    /**
     * 
     * Test to verify that the login page redirects to the about page
     */
    public function testLoginRedirect()
    {
        $user = User::factory()->create([
            'username' =>'newuser',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user, 'web');
        // open logout url
        $response = $this->get('logout');

        // Verify it redirects to the login page
        $response->assertRedirect('/login');

        // When we try to open the about page, we should be redirected to the login page 
        $response = $this->get(route('about.index'));
        $response->assertRedirect('/login');

        // Do POST login and verify redirect to about page
        $response = $this->post('login', [
            'username' => 'newuser',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('about.index'));
    }

    public function testLoginRedirectWithDevtoolsOpeningMapFile()
    {
        $user = User::factory()->create([
            'username' =>'newuser',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user, 'web');
        // open logout url
        $response = $this->get('logout');

        // Verify it redirects to the login page
        $response->assertRedirect('/login');

        // When we try to open the about page, we should be redirected to the login page 
        $response = $this->get(route('about.index'));
        $response->assertRedirect('/login');

        // Devtools opening a map file
        $response = $this->get('/js/missing.js.map');

        // Do POST login and verify redirect to about page not to the map file
        $response = $this->post('login', [
            'username' => 'newuser',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('about.index'));
    }
}
