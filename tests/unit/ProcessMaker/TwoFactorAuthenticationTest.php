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

    private function invokeGetUserDeliveryTotpSecret(User $user): string
    {
        $class = new ReflectionClass(TwoFactorAuthentication::class);
        $method = $class->getMethod('getUserDeliveryTotpSecret');
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

    public function testUserDeliveryTotpSecretIsNotDerivedFromUuidAndUsername(): void
    {
        $user = User::factory()->create([
            'uuid' => self::UUID,
            'username' => self::USERNAME,
        ]);

        $secret = $this->invokeGetUserDeliveryTotpSecret($user);
        $predictable = trim(Base32::encodeUpper(self::UUID . '_' . self::USERNAME), '=');

        $this->assertNotSame($predictable, $secret);

        $user->refresh();
        $this->assertSame($secret, $user->two_factor_delivery_secret);
        $this->assertEmpty($user->two_factor_secret);
    }

    public function testValidateCodeRejectsCodeDerivedFromPublicIdentityOnly(): void
    {
        $user = User::factory()->create([
            'uuid' => self::UUID,
            'username' => self::USERNAME,
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);

        $this->invokeGetUserDeliveryTotpSecret($user);

        $service = new TwoFactorAuthentication();
        $this->assertFalse(
            $service->validateCode($user, $this->codeFromPublicIdentity(self::UUID, self::USERNAME))
        );
        $this->assertFalse(
            $service->validateCode($user, $this->codeFromPublicIdentity(self::UUID, self::USERNAME, true))
        );
    }

    public function testValidateCodeAcceptsCodeFromStoredSecrets(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $authSecret = TOTP::generate()->getSecret();
        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => $authSecret,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        $service = new TwoFactorAuthentication();

        $appCode = TOTP::createFromSecret($authSecret)->now();
        $this->assertTrue($service->validateCode($user, $appCode));

        $emailOtp = TOTP::createFromSecret($deliverySecret);
        $emailOtp->setDigest('sha512');
        $emailOtp->setDigits(8);
        $emailOtp->setPeriod(300);
        $this->assertTrue($service->validateCode($user, $emailOtp->now()));
    }

    public function testValidateCodeRejectsSixDigitForEmailOnlyUser(): void
    {
        config(['password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL]]);

        $deliverySecret = TOTP::generate()->getSecret();
        $authSecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
            'two_factor_secret' => $authSecret,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        $sixDigitFromAuthSecret = TOTP::createFromSecret($authSecret)->now();
        $this->assertFalse((new TwoFactorAuthentication())->validateCode($user, $sixDigitFromAuthSecret));
    }

    public function testValidateCodeRejectsRandomCode(): void
    {
        $user = User::factory()->create([
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);

        $this->assertFalse((new TwoFactorAuthentication())->validateCode($user, '000000'));
    }
}
