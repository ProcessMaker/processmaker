<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ResetAuthAppTest extends TestCase
{
    use RequestHelper;

    public function test_admin_can_reset_authenticator_app_for_a_user(): void
    {
        $targetUser = User::factory()->create([
            'auth_app_configured_at' => now(),
        ]);

        $response = $this->apiCall('PUT', route('api.users.reset_auth_app', $targetUser));

        $response->assertStatus(200);
        $this->assertNull($targetUser->fresh()->auth_app_configured_at);
    }

    public function test_reset_returns_error_when_authenticator_is_not_configured(): void
    {
        $targetUser = User::factory()->create([
            'auth_app_configured_at' => null,
        ]);

        $response = $this->apiCall('PUT', route('api.users.reset_auth_app', $targetUser));

        $response->assertStatus(422);
    }
}
