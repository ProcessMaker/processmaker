<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Database\Seeders\PermissionSeeder;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Models\User;
use ProcessMaker\TwoFactorAuthentication;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TwoFactorAuthAppTest extends TestCase
{
    use RequestHelper {
        setUp as requestHelperSetUp;
    }

    protected function setUp(): void
    {
        $this->requestHelperSetUp();

        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => [TwoFactorAuthentication::AUTH_APP],
        ]);
    }

    protected function withUserSetup(): void
    {
        (new PermissionSeeder)->run();
    }

    public function test_otp_shows_authenticator_link_before_setup(): void
    {
        $this->user->forceFill(['auth_app_configured_at' => null])->save();

        $response = $this->webGet(route('2fa'));

        $response->assertStatus(200);
        $response->assertSee('Authenticator app', false);
    }

    public function test_otp_hides_authenticator_link_after_setup(): void
    {
        $this->user->forceFill(['auth_app_configured_at' => now()])->save();

        $response = $this->webGet(route('2fa'));

        $response->assertStatus(200);
        $response->assertDontSee('>Authenticator app<', false);
    }

    public function test_auth_app_qr_is_blocked_after_setup(): void
    {
        $this->user->forceFill(['auth_app_configured_at' => now()])->save();

        $response = $this->webGet(route('2fa.auth_app_qr'));

        $response->assertRedirect(route('2fa'));
    }

    public function test_valid_auth_app_code_marks_user_as_configured(): void
    {
        $this->user->forceFill(['auth_app_configured_at' => null])->save();

        $code = $this->generateAuthAppCode($this->user);

        $response = $this->webCall('POST', route('2fa.validate'), ['code' => $code]);

        $response->assertRedirect(route('login'));
        $this->assertNotNull($this->user->fresh()->auth_app_configured_at);
    }

    public function test_self_service_username_change_reopens_authenticator_enrollment(): void
    {
        $this->user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
            'auth_app_configured_at' => now(),
        ]);
        $this->user->giveDirectPermission('edit-personal-profile');
        $this->user->giveDirectPermission('edit-user-and-password');
        $this->user->refresh();
        $this->flushSession();

        $staleCode = $this->generateAuthAppCode($this->user);

        $response = $this->apiCall('PUT', route('api.users.update', $this->user), [
            'username' => 'reenroll-user',
            'firstname' => $this->user->firstname,
            'lastname' => $this->user->lastname,
            'title' => $this->user->title,
            'email' => $this->user->email,
            'status' => $this->user->status,
        ]);

        $response->assertStatus(204);
        $this->user->refresh();
        $this->assertSame('reenroll-user', $this->user->username);
        $this->assertNull($this->user->auth_app_configured_at);
        $this->assertFalse((new TwoFactorAuthentication())->validateCode($this->user, $staleCode));

        $this->apiCall('PUT', route('api.users.reset_auth_app', $this->user))->assertStatus(403);

        $otp = $this->webGet(route('2fa'));
        $otp->assertStatus(200);
        $otp->assertSee('Authenticator app', false);

        $this->webGet(route('2fa.auth_app_qr'))->assertOk();

        $response = $this->webCall('POST', route('2fa.validate'), [
            'code' => $this->generateAuthAppCode($this->user),
        ]);

        $response->assertRedirect(route('login'));
        $this->assertNotNull($this->user->fresh()->auth_app_configured_at);
    }

    public function test_admin_save_with_stale_snapshot_does_not_change_authenticator_enrollment(): void
    {
        $configuredAt = now()->startOfSecond();
        $targetUser = User::factory()->create([
            'auth_app_configured_at' => $configuredAt,
        ]);

        $response = $this->apiCall('PUT', route('api.users.update', $targetUser), $this->profileSnapshot($targetUser, [
            'firstname' => 'Renamed',
            'auth_app_configured_at' => null,
        ]));

        $response->assertStatus(204);
        $targetUser->refresh();
        $this->assertSame('Renamed', $targetUser->firstname);
        $this->assertEquals(
            $configuredAt->toDateTimeString(),
            $targetUser->auth_app_configured_at?->toDateTimeString()
        );
    }

    public function test_admin_save_cannot_mark_or_restore_authenticator_enrollment(): void
    {
        $targetUser = User::factory()->create([
            'auth_app_configured_at' => null,
        ]);
        $forgedAt = now()->subHour()->startOfSecond();

        $response = $this->apiCall('PUT', route('api.users.update', $targetUser), $this->profileSnapshot($targetUser, [
            'auth_app_configured_at' => $forgedAt->toDateTimeString(),
        ]));

        $response->assertStatus(204);
        $this->assertNull($targetUser->fresh()->auth_app_configured_at);

        $targetUser->forceFill(['auth_app_configured_at' => $forgedAt])->save();
        $targetUser->auth_app_configured_at = null;
        $targetUser->save();

        $response = $this->apiCall('PUT', route('api.users.update', $targetUser), $this->profileSnapshot($targetUser, [
            'auth_app_configured_at' => $forgedAt->toDateTimeString(),
        ]));

        $response->assertStatus(204);
        $this->assertNull($targetUser->fresh()->auth_app_configured_at);
    }

    public function test_create_user_ignores_authenticator_enrollment(): void
    {
        $username = 'new-auth-user-' . uniqid();

        $response = $this->apiCall('POST', route('api.users.store'), [
            'username' => $username,
            'firstname' => 'New',
            'lastname' => 'User',
            'email' => $username . '@example.com',
            'status' => 'ACTIVE',
            'password' => 'Password1!',
            'auth_app_configured_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(201);
        $this->assertNull(User::where('username', $username)->first()->auth_app_configured_at);
    }

    public function test_self_service_save_ignores_authenticator_timestamp_in_profile_snapshot(): void
    {
        $configuredAt = now()->startOfSecond();
        $this->user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
            'auth_app_configured_at' => $configuredAt,
        ]);
        $this->user->giveDirectPermission('edit-personal-profile');
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('PUT', route('api.users.update', $this->user), $this->profileSnapshot($this->user, [
            'firstname' => 'Updated',
            'auth_app_configured_at' => null,
        ]));

        $response->assertStatus(204);
        $this->user->refresh();
        $this->assertSame('Updated', $this->user->firstname);
        $this->assertEquals(
            $configuredAt->toDateTimeString(),
            $this->user->auth_app_configured_at?->toDateTimeString()
        );
    }

    public function test_username_change_still_clears_enrollment_when_snapshot_keeps_timestamp(): void
    {
        $configuredAt = now()->startOfSecond();
        $targetUser = User::factory()->create([
            'auth_app_configured_at' => $configuredAt,
        ]);

        $response = $this->apiCall('PUT', route('api.users.update', $targetUser), $this->profileSnapshot($targetUser, [
            'username' => 'renamed-enrolled-' . uniqid(),
            'auth_app_configured_at' => $configuredAt->toDateTimeString(),
        ]));

        $response->assertStatus(204);
        $targetUser->refresh();
        $this->assertNull($targetUser->auth_app_configured_at);
    }

    public function test_profile_update_without_username_change_keeps_authenticator_enrollment(): void
    {
        $configuredAt = now()->startOfSecond();
        $this->user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
            'auth_app_configured_at' => $configuredAt,
        ]);
        $this->user->giveDirectPermission('edit-personal-profile');
        $this->user->refresh();
        $this->flushSession();

        $response = $this->apiCall('PUT', route('api.users.update', $this->user), [
            'username' => $this->user->username,
            'firstname' => 'Updated',
            'lastname' => $this->user->lastname,
            'title' => $this->user->title,
            'email' => $this->user->email,
            'status' => $this->user->status,
        ]);

        $response->assertStatus(204);
        $this->assertEquals(
            $configuredAt->toDateTimeString(),
            $this->user->fresh()->auth_app_configured_at->toDateTimeString()
        );
    }

    private function profileSnapshot(User $user, array $overrides = []): array
    {
        return array_merge([
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'title' => $user->title,
            'email' => $user->email,
            'status' => $user->status,
            'auth_app_configured_at' => $user->auth_app_configured_at?->toDateTimeString(),
        ], $overrides);
    }

    private function generateAuthAppCode(User $user): string
    {
        $secret = trim(Base32::encodeUpper($user->uuid . '_' . $user->username), '=');
        $otp = TOTP::createFromSecret($secret);
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($user->username);

        return $otp->now();
    }
}
