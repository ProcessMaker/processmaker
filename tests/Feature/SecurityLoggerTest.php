<?php

namespace Tests\Feature;

use Tests\TestCase;
use PermissionSeeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\User;
use ProcessMaker\Models\SecurityLog;
use Tests\Feature\Shared\RequestHelper;

class SecurityLoggerTest extends TestCase
{
    /**
     * Test to ensure security events are logged
     */
    public function testLogSecurityEvents()
    {
        // Set the config to log security events
        config(['auth.log_auth_events' => true]);
        
        // Build a user with a specified password
        $user = factory(User::class)->create([
            'username' =>'newuser',
            'password' => Hash::make('password')
        ]);
        $this->assertDatabaseMissing('security_logs', ['user_id' => $user->id]);
        
        // Attempt to login with incorrect credentials
        $this->assertFalse(Auth::attempt([
            'username' => $user->username,
            'password' => 'invalidpassword'
        ]));
        $this->assertDatabaseHas('security_logs', ['event' => 'attempt', 'user_id' => $user->id]);
        
        // Attempt to login with correct password
        $this->assertTrue(Auth::attempt([
            'username' => $user->username,
            'password' => 'password'
        ]));
        $this->assertDatabaseHas('security_logs', ['event' => 'login', 'user_id' => $user->id]);
        
        // Attempt to logout
        Auth::logout();
        $this->assertDatabaseHas('security_logs', ['event' => 'logout', 'user_id' => $user->id]);
        
        // Disable security logging
        config(['auth.log_auth_events' => false]);
    }
    
    /**
     * Do not use transactions for this test
     */
    protected function connectionsToTransact()
    {
        return [];
    }
}
