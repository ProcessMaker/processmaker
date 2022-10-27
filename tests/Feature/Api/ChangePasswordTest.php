<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RequestHelper;

    const API_TEST_URL = '/password/change';

    public function testUserPasswordChangeWithInvalidPassword()
    {
        $this->actingAs($this->user);

        $user = Auth::user();

        // Send request with an invalid password value
        // Password must be at least 8 characters long
        $response = $this->apiCall('PUT', self::API_TEST_URL, [
            'password' => 'Proce5s',
            'confpassword' => 'Proce5s',
        ]);

        $response->assertStatus(422)
                 ->assertSeeText('Password must be at least 8 characters');

        // Password must contain one or more uppercase characters
        $response = $this->apiCall('PUT', self::API_TEST_URL, [
            'password' => 'process1',
            'confpassword' => 'process1',
        ]);

        $response->assertStatus(422)
                 ->assertSeeText('The password must contain at least one uppercase character');

        // Password must contain a number or special character
        $response = $this->apiCall('PUT', self::API_TEST_URL, [
            'password' => 'ProcessMaker',
            'confpassword' => 'ProcessMaker',
        ]);

        $response->assertStatus(422)
                 ->assertSeeText('The password must contain either a number or a special character');

        // Validate updated user password changed
        $updatedUser = User::where('id', $user->id)->first();

        $this->assertNotEquals($updatedUser, $user);

        // Validate flag force_change_password was
        // changed to 0 after password change
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'force_change_password' => 0,
        ]);
    }

    /**
     * Once the user changes their password, the flag must set to false
     */
    public function testUserChangePasswordMustSetFlagToFalse()
    {
        $this->actingAs($this->user);

        $user = Auth::user();

        // Post data with new password
        $response = $this->apiCall('PUT', self::API_TEST_URL, [
            'password' => 'ProcessMaker1',
            'confpassword' => 'ProcessMaker1',
        ]);

        // Validate the header status code
        $response->assertStatus(200);

        // Validate updated user password changed
        $updatedUser = User::where('id', $user->id)->first();

        $this->assertNotEquals($updatedUser, $user);

        // Validate flag force_change_password was
        // changed to 0 after password change
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'force_change_password' => 0,
        ]);
    }

    /**
     * When no password is sent, the flag must be set to true
     */
    public function testUserChangePasswordWithoutSendingPasswordMustKeepFlagInTrue()
    {
        $this->actingAs($this->user);

        // Initial force_change_password flag to true
        $this->user->force_change_password = 1;
        $this->user->save();

        // Post with empty params
        $response = $this->apiCall('PUT', self::API_TEST_URL);

        // Validate the header status code
        $response->assertStatus(422);

        // Validate flag force_change_password was
        // not changed when no password was sent
        $this->assertDatabaseHas('users', [
            'id' => Auth::user()->id,
            'force_change_password' => 1,
        ]);
    }
}
