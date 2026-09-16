<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

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

    public function test_otp_shows_authenticator_link_before_setup(): void
    {
        $this->user->update(['auth_app_configured_at' => null]);

        $response = $this->webGet(route('2fa'));

        $response->assertStatus(200);
        $response->assertSee('Authenticator app', false);
    }

    public function test_otp_hides_authenticator_link_after_setup(): void
    {
        $this->user->update(['auth_app_configured_at' => now()]);

        $response = $this->webGet(route('2fa'));

        $response->assertStatus(200);
        $response->assertDontSee('>Authenticator app<', false);
    }

    public function test_auth_app_qr_is_blocked_after_setup(): void
    {
        $this->user->update(['auth_app_configured_at' => now()]);

        $response = $this->webGet(route('2fa.auth_app_qr'));

        $response->assertRedirect(route('2fa'));
    }

    public function test_valid_auth_app_code_marks_user_as_configured(): void
    {
        $this->user->update(['auth_app_configured_at' => null]);

        $code = $this->generateAuthAppCode($this->user);

        $response = $this->webCall('POST', route('2fa.validate'), ['code' => $code]);

        $response->assertRedirect(route('login'));
        $this->assertNotNull($this->user->fresh()->auth_app_configured_at);
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
