<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    public function testShowChangeFormDisplaysPasswordRequirements(): void
    {
        config(['password-policies.minimum_length' => 10]);

        $user = User::factory()->create([
            'force_change_password' => 1,
        ]);

        Auth::login($user);

        $response = $this->get(route('password.change'));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.change');
        $response->assertSee(__('Password Requirements'), false);
        $response->assertSee(__('Minimum of :length characters in length', ['length' => 10]), false);
    }
}
