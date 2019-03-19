<?php

namespace Tests\Feature;

use Tests\TestCase;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Shared\RequestHelper;

class LoginTest extends TestCase
{

    use RequestHelper;

    /**
     * Test to ensure we can login via JSON
     *
     * @return void
     */
    public function testLoginViaJson()
    {
        $password = 'testpass';
        
        $user = factory(User::class)->create();
        $user->password = Hash::make($password);
        $user->save;

        // Assert that we get a 204 if the password is correct
        $response = $this->json('POST', '/login', [
            'username' => $user->username,
            'password' => $password,
        ]);
        $response->assertStatus(204);
        
        // Assert that we get a 422 if the password is incorrect
        $response = $this->json('POST', '/login', [
            'username' => $user->username,
            'password' => 'wrongpass',
        ]);
        $response->assertStatus(422);
    }
}
