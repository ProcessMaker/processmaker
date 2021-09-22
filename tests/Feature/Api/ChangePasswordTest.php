<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class ChangePasswordTest extends TestCase
{

    use RequestHelper, RefreshDatabase;

    const API_TEST_URL = '/password/change';

    /**
     * When user change password flag must change to false
     */
    public function testUserChangePasswordMustSetFlagToFalse()
    {
        $this->actingAs($this->user);

        $user = User::where('id', Auth::user()->id)->first();

        //Post data with new password
        $response = $this->apiCall('PUT', self::API_TEST_URL, [
            'password' => 'new_password'
        ]);

        //Validate the header status code
        $response->assertStatus(200);

        //Validate updated user password changed
        $updatedUser = User::where('id', Auth::user()->id)->first();
        $this->assertNotEquals($updatedUser, $user);

        //Validate Flag force_change_password was changed to 0 after password change
        $this->assertDatabaseHas('users', [
            'id' => Auth::user()->id,
            'force_change_password' => 0
        ]);
    }

    /**
     * When no password sended flag must be true
     */
    public function testUserChangePasswordWithoutSendingPasswordMustKeepFlagInTrue()
    {
        $this->actingAs($this->user);

        //Initial force_change_password flag to true
        $this->user->force_change_password = 1;
        $this->user->save();

        //Post with empty params
        $response = $this->apiCall('PUT', self::API_TEST_URL);

        //Validate the header status code
        $response->assertStatus(200);

        //Validate Flag force_change_password was not changed when no password sended
        $this->assertDatabaseHas('users', [
            'id' => Auth::user()->id,
            'force_change_password' => 1,
        ]);
    }
}
