<?php

namespace Tests\Feature\Api\User;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\User;
use Tests\TestCase;

/**
 * Tests the User Profile API Endpoints with expected values
 */
class UserProfileTest extends TestCase
{

    const API_TEST_PROFILE = '/api/1.0/admin/profile';

    public $user;

    /**
     *
     * These api endpoints can only work if you are authenticated
     */
    public function testUnauthenticated()
    {
        $response = $this->json('GET', self::API_TEST_PROFILE);
        $response->assertStatus(401);
    }

    /**
     * Verify profile api is working
     */
    public function testProfile()
    {
        $this->login();
        // Fetch via API
        $response = $this->actingAs($this->user, 'api')->json('GET', self::API_TEST_PROFILE);
        // Verify 200 status code
        $response->assertStatus(200);
        // Grab profile data
        $data = json_decode($response->getContent(), true);

        // Verify the uid matches the auth user uid
        $this->assertEquals($data['uid'], $this->user->uid);

    }

    private function login()
    {
        $this->user = factory(User::class)->create([
            'password' => Hash::make('password'),
        ]);
    }

}
