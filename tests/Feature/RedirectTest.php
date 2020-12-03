<?php

namespace Tests\Feature;

use Tests\TestCase;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $user = factory(User::class)->create([
            'is_administrator' => false,
        ]);
        Auth::login($user);
        $response = $this->get('/requests');
        $response->assertStatus(200);
        $response->assertViewIs('requests.index');
        Auth::logout();
        $response = $this->get('/requests'); 
        //302 because we want to make sure they are being redirected
        $response->assertStatus(302);
    }
}
