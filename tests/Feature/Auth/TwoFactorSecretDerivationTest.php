<?php

namespace Tests\Feature\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;
use ProcessMaker\Models\DataStore;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;
use ProcessMaker\Repositories\TokenRepository;
use ProcessMaker\TwoFactorAuthentication;
use ReflectionMethod;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * The second factor must not be computable from the user record that the API
 * and process data already expose. These tests fail until that secret is
 * independent of the username and uuid.
 */
class TwoFactorSecretDerivationTest extends TestCase
{
    use RequestHelper;

    private const RENAMED_USERNAME = 'renamed.user';

    public function testEmailCodeFromTheUserApiIsRejectedAndTheIssuedCodeStillWorksAfterRename(): void
    {
        Notification::fake();
        $this->enableTwoFactor([TwoFactorAuthentication::EMAIL]);
        $victim = $this->makeVictim([TwoFactorAuthentication::EMAIL]);
        $identity = $this->identityFromUserApi($victim);

        $form = $this->actingAs($victim, 'web')->get(route('2fa'));
        $form->assertOk();

        $issued = $this->issuedEmailCode($victim);
        $predicted = $this->codeFromPublicIdentity($identity['uuid'], $identity['username'], true);

        $this->assertNotSame($predicted, $issued);
        $this->assertStringContainsString($issued, $this->issuedMailLine($victim));
        $this->assertTrue((new TwoFactorAuthentication())->validateCode($victim, $issued));
        $this->assertFalse((new TwoFactorAuthentication())->validateCode($victim, $predicted));

        $victim->username = self::RENAMED_USERNAME;
        $victim->save();

        $this->assertTrue((new TwoFactorAuthentication())->validateCode($victim->fresh(), $issued));
        $this->assertFalse((new TwoFactorAuthentication())->validateCode(
            $victim->fresh(),
            $this->codeFromPublicIdentity($identity['uuid'], self::RENAMED_USERNAME, true)
        ));
    }

    public function testPredictedEmailCodeDoesNotPassTheValidationRouteOrTheTwoFactorGate(): void
    {
        Notification::fake();
        $this->enableTwoFactor([TwoFactorAuthentication::EMAIL]);
        $victim = $this->makeVictim([TwoFactorAuthentication::EMAIL]);
        $identity = $this->identityFromUserApi($victim);
        $predicted = $this->codeFromPublicIdentity($identity['uuid'], $identity['username'], true);

        $this->actingAs($victim, 'web')->get(route('2fa'));

        $rejected = $this->actingAs($victim, 'web')->post(route('2fa.validate'), [
            'code' => $predicted,
        ]);
        $rejected->assertSessionHas(TwoFactorAuthController::TFA_VALIDATED, false);
        $rejected->assertRedirect(route('2fa'));

        $gated = $this->actingAs($victim, 'web')->get(route('customize-ui.edit'));
        $gated->assertRedirect(route('2fa'));

        $issued = $this->issuedEmailCode($victim);
        $accepted = $this->actingAs($victim, 'web')->post(route('2fa.validate'), [
            'code' => $issued,
        ]);
        $accepted->assertSessionHas(TwoFactorAuthController::TFA_VALIDATED, true);

        $unlocked = $this->actingAs($victim, 'web')->get(route('customize-ui.edit'));
        $this->assertFalse($unlocked->isRedirect(route('2fa')));
    }

    public function testAuthenticatorQrFromTheUserApiIsNotThePublishedQr(): void
    {
        $this->enableTwoFactor([TwoFactorAuthentication::AUTH_APP]);
        $victim = $this->makeVictim([TwoFactorAuthentication::AUTH_APP]);
        $identity = $this->identityFromUserApi($victim);

        $published = base64_decode((new TwoFactorAuthentication())->generateQr($victim));
        $derivedImage = $this->qrImageForPublicIdentity($identity['uuid'], $identity['username']);

        $this->assertNotSame($derivedImage, $published);
    }

    public function testAuthenticatorCodeFromTheUserApiIsRejected(): void
    {
        $this->enableTwoFactor([TwoFactorAuthentication::AUTH_APP]);
        $victim = $this->makeVictim([TwoFactorAuthentication::AUTH_APP]);
        $identity = $this->identityFromUserApi($victim);
        $predicted = $this->codeFromPublicIdentity($identity['uuid'], $identity['username'], false);

        $this->assertFalse((new TwoFactorAuthentication())->validateCode($victim, $predicted));

        $rejected = $this->actingAs($victim, 'web')->post(route('2fa.validate'), [
            'code' => $predicted,
        ]);
        $rejected->assertSessionHas(TwoFactorAuthController::TFA_VALIDATED, false);
        $rejected->assertRedirect(route('2fa'));
    }

    public function testProcessRequestUserIncludeCannotMintACode(): void
    {
        $this->enableTwoFactor([TwoFactorAuthentication::EMAIL]);
        $victim = $this->makeVictim([TwoFactorAuthentication::EMAIL]);
        $request = ProcessRequest::factory()->create([
            'user_id' => $victim->id,
        ]);

        $shown = $this->apiCall('GET', '/requests/' . $request->id . '?include=user');
        $shown->assertOk();
        $included = $shown->json('user');

        $this->assertSame($victim->username, $included['username']);
        $this->assertSame($victim->uuid, $included['uuid']);
        $this->assertFalse((new TwoFactorAuthentication())->validateCode(
            $victim,
            $this->codeFromPublicIdentity($included['uuid'], $included['username'], true)
        ));
    }

    public function testActivityUserDataCannotMintACode(): void
    {
        $this->enableTwoFactor([TwoFactorAuthentication::EMAIL]);
        $victim = $this->makeVictim([TwoFactorAuthentication::EMAIL]);

        $instance = new ProcessRequest();
        $instance->setDataStore(new DataStore());
        $repository = app(TokenRepository::class);
        $addUserToData = new ReflectionMethod($repository, 'addUserToData');
        $addUserToData->invoke($repository, $instance, $victim);
        $stored = $instance->getDataStore()->getData('_user');

        $this->assertSame($victim->username, $stored['username']);
        $this->assertSame($victim->uuid, $stored['uuid']);
        $this->assertArrayNotHasKey('password', $stored);
        $this->assertFalse((new TwoFactorAuthentication())->validateCode(
            $victim,
            $this->codeFromPublicIdentity($stored['uuid'], $stored['username'], true)
        ));
        $this->assertFalse((new TwoFactorAuthentication())->validateCode(
            $victim,
            $this->codeFromPublicIdentity($stored['uuid'], $stored['username'], false)
        ));
    }

    private function enableTwoFactor(array $methods): void
    {
        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => $methods,
            'password-policies.expiration_days' => false,
        ]);
    }

    private function makeVictim(array $methods): User
    {
        return User::factory()->create([
            'username' => 'victim.user',
            'status' => 'ACTIVE',
            'force_change_password' => 0,
            'preferences_2fa' => $methods,
        ]);
    }

    /**
     * @return array{uuid: string, username: string}
     */
    private function identityFromUserApi(User $victim): array
    {
        $shown = $this->apiCall('GET', '/users/' . $victim->id);
        $shown->assertOk();
        $payload = $shown->json();

        $listed = $this->apiCall('GET', '/users?filter=' . urlencode($victim->username));
        $listed->assertOk();
        $match = collect($listed->json('data'))->firstWhere('id', $victim->id);

        $this->assertIsArray($match);
        $this->assertSame($payload['uuid'], $match['uuid']);
        $this->assertSame($payload['username'], $match['username']);
        $this->assertSame($victim->uuid, $payload['uuid']);
        $this->assertSame($victim->username, $payload['username']);
        $this->assertArrayNotHasKey('password', $payload);

        return [
            'uuid' => $payload['uuid'],
            'username' => $payload['username'],
        ];
    }

    private function issuedEmailCode(User $user): string
    {
        $notifications = Notification::sent($user, TwoFactorAuthNotification::class);
        $this->assertCount(1, $notifications);

        return $notifications->first()->toArray($user)['code'];
    }

    private function issuedMailLine(User $user): string
    {
        $notification = Notification::sent($user, TwoFactorAuthNotification::class)->first();
        $mail = $notification->toMail($user);

        return implode("\n", $mail->introLines);
    }

    private function codeFromPublicIdentity(string $uuid, string $username, bool $emailOrSms): string
    {
        $secret = trim(Base32::encodeUpper($uuid . '_' . $username), '=');
        $otp = TOTP::createFromSecret($secret);
        if ($emailOrSms) {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

        return $otp->now();
    }

    private function qrImageForPublicIdentity(string $uuid, string $username): string
    {
        $secret = trim(Base32::encodeUpper($uuid . '_' . $username), '=');
        $otp = TOTP::createFromSecret($secret);
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($username);

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(250),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($otp->getProvisioningUri());
    }
}
