<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Hash;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;
use ProcessMaker\Models\User;
use ProcessMaker\TwoFactorAuthentication;
use Tests\TestCase;

/**
 * FOUR-34260 — 2FA regression: codes must not be derivable from uuid + username.
 */
class TwoFactorPredictableSecretTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => [TwoFactorAuthentication::AUTH_APP],
        ]);
    }

    private function codeFromPublicIdentity(string $uuid, string $username, string $mode = 'app'): string
    {
        $secret = trim(Base32::encodeUpper($uuid . '_' . $username), '=');
        $otp = TOTP::createFromSecret($secret);

        if ($mode === 'email') {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

        return $otp->now();
    }

    private function victimAtTwoFactorGate(): User
    {
        return User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
        ]);
    }

    private function postTwoFactorCode(User $sessionUser, string $code)
    {
        return $this->actingAs($sessionUser, 'web')
            ->post(route('2fa.validate'), ['code' => $code]);
    }

    public function testProtectedRouteRedirectsToTwoFactorUntilValidated(): void
    {
        $victim = $this->victimAtTwoFactorGate();

        $this->actingAs($victim, 'web')
            ->get(route('about.index'))
            ->assertRedirect(route('2fa'));
    }

    public function testWrongCodeDoesNotClearTwoFactorGate(): void
    {
        $victim = $this->victimAtTwoFactorGate();

        $this->postTwoFactorCode($victim, '000000')
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));

        $this->actingAs($victim, 'web')
            ->get(route('about.index'))
            ->assertRedirect(route('2fa'));
    }

    public function testCodeDerivedFromAnotherUsersPublicIdentityDoesNotValidateVictimSession(): void
    {
        $victim = $this->victimAtTwoFactorGate();
        $attacker = User::factory()->create();

        $codeForAttacker = $this->codeFromPublicIdentity($attacker->uuid, $attacker->username);

        $this->postTwoFactorCode($victim, $codeForAttacker)
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
    }

    public function testVictimSessionRejectsCodeComputedFromPublicUuidAndUsernameOnly(): void
    {
        $victim = $this->victimAtTwoFactorGate();
        $attacker = User::factory()->create();

        $harvested = User::query()
            ->whereKey($victim->id)
            ->first(['uuid', 'username']);

        $this->assertNotSame($attacker->uuid, $harvested->uuid);

        $code = $this->codeFromPublicIdentity($harvested->uuid, $harvested->username);

        $this->postTwoFactorCode($victim, $code)
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));

        $this->actingAs($victim, 'web')
            ->get(route('about.index'))
            ->assertRedirect(route('2fa'));
    }

    public function testVictimSessionRejectsEightDigitEmailModeCodeFromPublicIdentity(): void
    {
        config([
            'password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL],
        ]);

        $victim = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);

        $code = $this->codeFromPublicIdentity($victim->uuid, $victim->username, 'email');

        $this->postTwoFactorCode($victim, $code)
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
    }

    public function testVictimSessionAcceptsCodeFromStoredSecret(): void
    {
        $secret = TOTP::generate()->getSecret();
        $victim = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
            'two_factor_secret' => $secret,
        ]);

        $code = TOTP::createFromSecret($secret)->now();

        $this->postTwoFactorCode($victim, $code)
            ->assertRedirect(route('login'));

        $this->assertTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));

        $this->actingAs($victim, 'web')
            ->get(route('about.index'))
            ->assertOk();
    }
}
