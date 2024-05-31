<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RequestHelper;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test401RedirectsToLogin()
    {
        $user = User::factory()->create([
            'is_administrator' => false,
        ]);
        Auth::login($user);
        $response = $this->get('/cases');
        $response->assertStatus(200);
        $response->assertViewIs('cases.index');
        Auth::logoutCurrentDevice();
        $response = $this->get('/cases');
        //302 because we want to make sure they are being redirected
        $response->assertStatus(302);
    }

    /**
     * Redirect to password change when user has flag to true
     */
    public function testRedirectToForcePasswordChange()
    {
        $user = User::factory()->create([
            'force_change_password' => 1,
        ]);

        Auth::login($user);

        $this->get('/requests')
            ->assertStatus(302)
            ->assertRedirect('password/change');
    }
}
