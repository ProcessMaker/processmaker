<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker;

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Models\User;
use ProcessMaker\TwoFactorAuthentication;
use ReflectionClass;
use Tests\TestCase;

/**
 * FOUR-34260 — 2FA must not derive TOTP secrets from public identity fields.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    private const UUID = 'a31dd972-b713-4538-ac0b-c8c4182bd157';

    private const USERNAME = 'pmadmin';

    private function invokeGetUserTotpSecret(User $user): string
    {
        $class = new ReflectionClass(TwoFactorAuthentication::class);
        $method = $class->getMethod('getUserTotpSecret');
        $method->setAccessible(true);

        return $method->invoke(new TwoFactorAuthentication(), $user);
    }

    private function codeFromPublicIdentity(string $uuid, string $username, bool $emailMode = false): string
    {
        $secret = trim(Base32::encodeUpper($uuid . '_' . $username), '=');
        $otp = TOTP::createFromSecret($secret);

        if ($emailMode) {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

        return $otp->now();
    }

    public function testUserTotpSecretIsNotDerivedFromUuidAndUsername(): void
    {
        $user = User::factory()->create([
            'uuid' => self::UUID,
            'username' => self::USERNAME,
        ]);

        $secret = $this->invokeGetUserTotpSecret($user);
        $predictable = trim(Base32::encodeUpper(self::UUID . '_' . self::USERNAME), '=');

        $this->assertNotSame($predictable, $secret);

        $user->refresh();
        $this->assertSame($secret, $user->two_factor_secret);
    }

    public function testValidateCodeRejectsCodeDerivedFromPublicIdentityOnly(): void
    {
        $user = User::factory()->create([
            'uuid' => self::UUID,
            'username' => self::USERNAME,
        ]);

        $this->invokeGetUserTotpSecret($user);

        $this->assertFalse(
            (new TwoFactorAuthentication())->validateCode(
                $user,
                $this->codeFromPublicIdentity(self::UUID, self::USERNAME)
            )
        );
        $this->assertFalse(
            (new TwoFactorAuthentication())->validateCode(
                $user,
                $this->codeFromPublicIdentity(self::UUID, self::USERNAME, true)
            )
        );
    }

    public function testValidateCodeAcceptsCodeFromStoredSecret(): void
    {
        $secret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'two_factor_secret' => $secret,
        ]);

        $appCode = TOTP::createFromSecret($secret)->now();
        $this->assertTrue((new TwoFactorAuthentication())->validateCode($user, $appCode));

        $emailOtp = TOTP::createFromSecret($secret);
        $emailOtp->setDigest('sha512');
        $emailOtp->setDigits(8);
        $emailOtp->setPeriod(300);
        $this->assertTrue((new TwoFactorAuthentication())->validateCode($user, $emailOtp->now()));
    }

    public function testValidateCodeRejectsRandomCode(): void
    {
        $user = User::factory()->create();

        $this->assertFalse((new TwoFactorAuthentication())->validateCode($user, '000000'));
    }
}
