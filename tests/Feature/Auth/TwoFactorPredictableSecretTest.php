<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;
use ProcessMaker\TwoFactorAuthentication;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * FOUR-34260 — 2FA regression: codes must not be derivable from uuid + username.
 */
class TwoFactorPredictableSecretTest extends TestCase
{
    use RequestHelper;

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

    private function putPendingAuthenticatorSecret(User $user, string $secret): void
    {
        session()->put(TwoFactorAuthentication::PENDING_AUTHENTICATOR_SECRET, $secret);
        session()->put(TwoFactorAuthentication::PENDING_AUTHENTICATOR_USER_ID, $user->id);
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

    public function testLegacyUserProvisioningWhenSecretIsMissing(): void
    {
        Notification::fake();

        $authOnly = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
            'two_factor_secret' => null,
        ]);
        $this->actingAs($authOnly, 'web')
            ->get(route('2fa'))
            ->assertRedirect(route('2fa.auth_app_qr'));

        $this->actingAs($authOnly, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertOk();

        $this->assertEmpty($authOnly->fresh()->two_factor_secret);
        $this->assertNotEmpty(session(TwoFactorAuthentication::PENDING_AUTHENTICATOR_SECRET));

        $this->actingAs($authOnly, 'web')
            ->get(route('2fa'))
            ->assertOk()
            ->assertViewIs('auth.2fa.otp');

        config(['password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL]]);
        $emailUser = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
            'two_factor_secret' => null,
        ]);
        $this->actingAs($emailUser, 'web')->get(route('2fa'))->assertOk();

        Notification::assertSentTo($emailUser, TwoFactorAuthNotification::class);
        $emailUser->refresh();
        $this->assertNotEmpty($emailUser->two_factor_delivery_secret);
        $this->assertEmpty($emailUser->two_factor_secret);
    }

    public function testAuthAppQrRouteRequiresAuthenticatorEnrollment(): void
    {
        config(['password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL]]);

        $emailUser = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);

        $this->actingAs($emailUser, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertRedirect(route('2fa'));

        $secret = TOTP::generate()->getSecret();
        $authUser = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
            'two_factor_secret' => $secret,
        ]);

        config(['password-policies.2fa_method' => [TwoFactorAuthentication::AUTH_APP]]);

        $this->actingAs($authUser, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertRedirect(route('2fa'));
    }

    /**
     * Stateless auth:api must not narrow preferences_2fa before the same principal
     * completes web 2FA (blocks Bearer PUT bypass of delivery step-up).
     */
    public function testStatelessApiCannotUpdatePreferences2faBeforeWebTwoFactorValidated(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'is_administrator' => true,
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
        ]);

        $payload = [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'status' => $user->status,
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
        ];

        $this->actingAs($user, 'api')
            ->putJson('/api/1.0/users/' . $user->id, $payload)
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                __('Two-factor authentication must be completed before updating 2FA preferences.')
            );

        $freshPreferences = $user->fresh()->preferences_2fa;
        $this->assertContains(TwoFactorAuthentication::EMAIL, $freshPreferences);
        $this->assertContains(TwoFactorAuthentication::AUTH_APP, $freshPreferences);
    }

    public function testForeignSsoWebSessionCannotBypassTwoFactorGateOnApiSelfUpdate(): void
    {
        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $victim = User::factory()->create([
            'is_administrator' => true,
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'cell' => '15550001111',
        ]);

        $otherSsoUser = User::factory()->create();
        $this->actingAs($otherSsoUser, 'web');
        session()->put('sso_driver', 'saml');

        $payload = [
            'username' => $victim->username,
            'firstname' => $victim->firstname,
            'lastname' => $victim->lastname,
            'email' => $victim->email,
            'status' => $victim->status,
            'cell' => '19998887777',
        ];

        $this->actingAs($victim, 'api')
            ->putJson('/api/1.0/users/' . $victim->id, $payload)
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                __('Two-factor authentication must be completed before updating 2FA preferences.')
            );

        $this->assertSame('15550001111', $victim->fresh()->cell);
    }

    public function testStatelessApiCannotChangeEmailBeforeWebTwoFactorValidated(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'is_administrator' => true,
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
        ]);

        $payload = [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => 'attacker-' . $user->email,
            'status' => $user->status,
            'valpassword' => 'password',
        ];

        $this->actingAs($user, 'api')
            ->putJson('/api/1.0/users/' . $user->id, $payload)
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                __('Two-factor authentication must be completed before updating 2FA preferences.')
            );

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function testCannotBypassDeliveryWithPendingAuthenticatorEnrollment(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
        ]);

        session()->put(TwoFactorAuthentication::CHALLENGE_METHODS_SNAPSHOT, [
            TwoFactorAuthentication::AUTH_APP,
            TwoFactorAuthentication::EMAIL,
        ]);

        $pendingSecret = TOTP::generate()->getSecret();
        $this->putPendingAuthenticatorSecret($user, $pendingSecret);

        $this->actingAs($user, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertRedirect(route('2fa'));

        $this->postTwoFactorCode($user, TOTP::createFromSecret($pendingSecret)->now())
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
        $this->assertEmpty($user->fresh()->two_factor_secret);
    }

    /**
     * Enrollment still requires delivery when the login snapshot includes it, even if
     * stored preferences were narrowed outside the API (historical bypass shape).
     */
    public function testNarrowedPreferencesCannotSkipDeliveryStepUpWhenPolicyRequiresIt(): void
    {
        Notification::fake();

        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
        ]);

        session()->put(TwoFactorAuthentication::CHALLENGE_METHODS_SNAPSHOT, [
            TwoFactorAuthentication::AUTH_APP,
            TwoFactorAuthentication::EMAIL,
        ]);

        // Simulates narrowed prefs without going through UserController (not the API guard).
        $user->preferences_2fa = [TwoFactorAuthentication::AUTH_APP];
        $user->saveQuietly();

        $pendingSecret = TOTP::generate()->getSecret();
        $this->putPendingAuthenticatorSecret($user, $pendingSecret);

        $this->actingAs($user, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertRedirect(route('2fa'));

        $this->postTwoFactorCode($user, TOTP::createFromSecret($pendingSecret)->now())
            ->assertRedirect(route('2fa'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
        $this->assertEmpty($user->fresh()->two_factor_secret);
    }

    public function testMixedMethodsRedirectToQrAfterDeliveryCode(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        $emailOtp = TOTP::createFromSecret($deliverySecret);
        $emailOtp->setDigest('sha512');
        $emailOtp->setDigits(8);
        $emailOtp->setPeriod(300);

        $this->actingAs($user, 'web')
            ->post(route('2fa.validate'), ['code' => $emailOtp->now()])
            ->assertRedirect(route('2fa.auth_app_qr'));

        $this->assertNotTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
        $this->assertTrue(session()->get(TwoFactorAuthentication::DELIVERY_STEP_UP));

        $this->actingAs($user, 'web')
            ->get(route('about.index'))
            ->assertRedirect(route('2fa'));
    }

    public function testMixedMethodsDoesNotResendDeliveryOtpDuringAuthenticatorEnrollment(): void
    {
        Notification::fake();

        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        session()->put(TwoFactorAuthentication::CHALLENGE_METHODS_SNAPSHOT, [
            TwoFactorAuthentication::AUTH_APP,
            TwoFactorAuthentication::EMAIL,
        ]);
        session()->put(TwoFactorAuthentication::DELIVERY_STEP_UP, true);
        $this->putPendingAuthenticatorSecret($user, TOTP::generate()->getSecret());

        $this->actingAs($user, 'web')
            ->get(route('2fa'))
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function testSendAgainDoesNotResendDeliveryOtpDuringAuthenticatorEnrollment(): void
    {
        Notification::fake();

        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
        ]);

        session()->put(TwoFactorAuthentication::CHALLENGE_METHODS_SNAPSHOT, [
            TwoFactorAuthentication::AUTH_APP,
            TwoFactorAuthentication::EMAIL,
        ]);
        session()->put(TwoFactorAuthentication::DELIVERY_STEP_UP, true);
        $this->putPendingAuthenticatorSecret($user, TOTP::generate()->getSecret());

        $this->actingAs($user, 'web')
            ->get(route('2fa.send_again'))
            ->assertRedirect(route('2fa'));

        Notification::assertNothingSent();
    }

    public function testMixedMethodsSecondDeliveryCodeStaysOnOtpForm(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        session()->put(TwoFactorAuthentication::CHALLENGE_METHODS_SNAPSHOT, [
            TwoFactorAuthentication::AUTH_APP,
            TwoFactorAuthentication::EMAIL,
        ]);
        session()->put(TwoFactorAuthentication::DELIVERY_STEP_UP, true);
        $this->putPendingAuthenticatorSecret($user, TOTP::generate()->getSecret());

        $emailOtp = TOTP::createFromSecret($deliverySecret);
        $emailOtp->setDigest('sha512');
        $emailOtp->setDigits(8);
        $emailOtp->setPeriod(300);

        $this->actingAs($user, 'web')
            ->post(route('2fa.validate'), ['code' => $emailOtp->now()])
            ->assertRedirect(route('2fa'));
    }

    public function testMixedMethodsAllowAuthenticatorEnrollmentAfterDeliveryStepUp(): void
    {
        config([
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        session()->put(TwoFactorAuthentication::DELIVERY_STEP_UP, true);
        $pendingSecret = TOTP::generate()->getSecret();
        $this->putPendingAuthenticatorSecret($user, $pendingSecret);

        $this->actingAs($user, 'web')
            ->get(route('2fa.auth_app_qr'))
            ->assertOk();

        $this->postTwoFactorCode($user, TOTP::createFromSecret($pendingSecret)->now())
            ->assertRedirect(route('login'));

        $this->assertTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
        $this->assertSame($pendingSecret, $user->fresh()->two_factor_secret);
    }

    public function testSpoofedSsoDriverCannotBypassApiTwoFactorGateWithoutSsoBinding(): void
    {
        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
        ]);

        $user = User::factory()->create([
            'is_administrator' => true,
            'preferences_2fa' => [
                TwoFactorAuthentication::AUTH_APP,
                TwoFactorAuthentication::EMAIL,
            ],
            'two_factor_secret' => null,
            'cell' => '15550001111',
        ]);

        $this->actingAs($user, 'web');
        session()->put('sso_driver', 'saml');

        $payload = [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'status' => $user->status,
            'cell' => '19998887777',
        ];

        $this->actingAs($user, 'api')
            ->putJson('/api/1.0/users/' . $user->id, $payload)
            ->assertStatus(422);

        $this->assertSame('15550001111', $user->fresh()->cell);
    }

    public function testAdministratorCanUpdateAnotherUsersCellWithoutSelfServiceTwoFactorGate(): void
    {
        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL],
        ]);

        $admin = User::factory()->create(['is_administrator' => true]);
        $other = User::factory()->create([
            'cell' => '15550001111',
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);

        $payload = [
            'username' => $other->username,
            'firstname' => $other->firstname,
            'lastname' => $other->lastname,
            'email' => $other->email,
            'status' => $other->status,
            'cell' => '19998887777',
        ];

        $this->actingAs($admin, 'api')
            ->putJson('/api/1.0/users/' . $other->id, $payload)
            ->assertStatus(204);

        $this->assertSame('19998887777', $other->fresh()->cell);
    }

    public function testEmailOnlyUserClearsTwoFactorGateAfterEightDigitDeliveryCode(): void
    {
        config([
            'password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL],
        ]);

        $deliverySecret = TOTP::generate()->getSecret();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
            'two_factor_delivery_secret' => $deliverySecret,
        ]);

        $emailOtp = TOTP::createFromSecret($deliverySecret);
        $emailOtp->setDigest('sha512');
        $emailOtp->setDigits(8);
        $emailOtp->setPeriod(300);

        $this->actingAs($user, 'web')
            ->post(route('2fa.validate'), ['code' => $emailOtp->now()])
            ->assertRedirect(route('login'));

        $this->assertTrue(session()->get(TwoFactorAuthController::TFA_VALIDATED));
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

    public function testSsoBindingWithoutSsoDriverSessionStillRequiresTwoFactorCompletion(): void
    {
        config(['password-policies.2fa_method' => [TwoFactorAuthentication::EMAIL]]);

        $ldapUser = User::factory()->create([
            'is_administrator' => true,
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::EMAIL],
        ]);
        $ldapUser->meta = new \stdClass();
        $ldapUser->meta->authenticationType = 'ldap';
        $ldapUser->saveQuietly();

        // Login with password, no sso_driver in session
        $this->actingAs($ldapUser, 'web');
        $this->assertFalse(session()->has('sso_driver'));

        // Attempt self-service cell update without TFA_VALIDATED using bearer token
        $payload = [
            'username' => $ldapUser->username,
            'firstname' => $ldapUser->firstname,
            'lastname' => $ldapUser->lastname,
            'email' => $ldapUser->email,
            'status' => $ldapUser->status,
            'cell' => '+1234567890',
        ];

        $response = $this->actingAs($ldapUser, 'api')
            ->putJson("/api/1.0/users/{$ldapUser->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Two-factor authentication must be completed before updating 2FA preferences.',
        ]);
    }

    public function testPendingAuthenticatorSecretBelongsToSpecificUser(): void
    {
        config(['password-policies.2fa_method' => [TwoFactorAuthentication::AUTH_APP]]);

        $owner = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
        ]);
        $attacker = User::factory()->create([
            'password' => Hash::make('password'),
            'preferences_2fa' => [TwoFactorAuthentication::AUTH_APP],
        ]);

        // Owner generates QR and gets pending secret
        $this->actingAs($owner, 'web');
        $this->get(route('2fa.auth_app_qr'));
        $this->assertTrue(session()->has(TwoFactorAuthentication::PENDING_AUTHENTICATOR_SECRET));
        $ownerSecret = session()->get(TwoFactorAuthentication::PENDING_AUTHENTICATOR_SECRET);

        // Attacker switches into same session (session fixation scenario)
        $this->actingAs($attacker, 'web');

        // Attacker visits QR page - should NOT reuse owner's secret
        $this->get(route('2fa.auth_app_qr'));
        $attackerSecret = session()->get(TwoFactorAuthentication::PENDING_AUTHENTICATOR_SECRET);

        $this->assertNotEquals($ownerSecret, $attackerSecret);

        // Attacker code from owner's secret should not validate
        $ownerCode = TOTP::createFromSecret($ownerSecret)->now();
        $this->postTwoFactorCode($attacker, $ownerCode)
            ->assertRedirect(route('2fa'));

        // Attacker should not be able to enroll using owner's secret
        $this->assertEmpty($attacker->fresh()->two_factor_secret);
        $this->assertNotEquals($ownerSecret, $attacker->fresh()->two_factor_secret);
    }
}
