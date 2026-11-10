<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;
use ProcessMaker\TwoFactorAuthentication;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use Throwable;

/**
 * Codes derived from a user's public uuid and username must not satisfy
 * two-factor authentication. These assertions fail while that derivation
 * is what generateSecret uses.
 */
class DerivedTwoFactorSecretTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    public function setupPassportKeys(): void
    {
        config([
            'passport.public_key' => file_get_contents(base_path('tests/storage/keys/public.key')),
            'passport.private_key' => file_get_contents(base_path('tests/storage/keys/private.key')),
        ]);
    }

    public static function listPermissions(): array
    {
        return [
            'view-users' => ['view-users'],
            'create-processes' => ['create-processes'],
            'edit-processes' => ['edit-processes'],
            'create-projects' => ['create-projects'],
            'view-projects' => ['view-projects'],
        ];
    }

    public static function showPermissions(): array
    {
        return [
            'view-users' => ['view-users'],
            'create-projects' => ['create-projects'],
        ];
    }

    #[DataProvider('listPermissions')]
    public function testDerivedCodesFromTheUserDirectoryDoNotPassValidation(string $permission): void
    {
        $victim = $this->victim();
        $this->ensurePermission($permission);
        $reader = User::factory()->create([
            'username' => 'reader.' . $permission,
            'is_administrator' => false,
        ]);
        $reader->giveDirectPermission($permission);
        $this->user = $reader;

        $response = $this->apiCall('GET', '/users', [
            'filter' => $victim->username,
            'per_page' => 50,
        ]);
        $response->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $victim->id);
        $this->assertIsArray($row);
        $this->assertSame($victim->username, $row['username']);
        $this->assertSame($victim->uuid, $row['uuid']);

        $authenticator = new TwoFactorAuthentication();
        $emailCode = $this->currentDerivedCode($row['uuid'], $row['username'], false);
        $appCode = $this->currentDerivedCode($row['uuid'], $row['username'], true);
        $this->assertSame(8, strlen($emailCode));
        $this->assertSame(6, strlen($appCode));
        $this->assertSame(
            [false, false],
            [
                $authenticator->validateCode($victim, $emailCode),
                $authenticator->validateCode($victim, $appCode),
            ]
        );
    }

    #[DataProvider('showPermissions')]
    public function testShowReturnsTheSamePublicInputsForViewUsersAndCreateProjects(string $permission): void
    {
        $victim = $this->victim();
        $this->ensurePermission($permission);
        $reader = User::factory()->create([
            'username' => 'show.' . $permission,
            'is_administrator' => false,
        ]);
        $reader->giveDirectPermission($permission);
        $this->user = $reader;

        $response = $this->apiCall('GET', '/users/' . $victim->id);
        $response->assertOk();
        $response->assertJsonPath('username', $victim->username);
        $response->assertJsonPath('uuid', $victim->uuid);

        $authenticator = new TwoFactorAuthentication();
        $this->assertSame(
            [false, false],
            [
                $authenticator->validateCode(
                    $victim,
                    $this->currentDerivedCode($response->json('uuid'), $response->json('username'), false)
                ),
                $authenticator->validateCode(
                    $victim,
                    $this->currentDerivedCode($response->json('uuid'), $response->json('username'), true)
                ),
            ]
        );
    }

    public function testUserWithoutAListPermissionCannotReadTheDirectory(): void
    {
        $reader = User::factory()->create([
            'username' => 'reader.view-processes',
            'is_administrator' => false,
        ]);
        $reader->giveDirectPermission('view-processes');
        $this->user = $reader;

        $this->apiCall('GET', '/users')->assertForbidden();
    }

    public function testSubmittedEmailCodeFromPublicInputsDoesNotClearTheChallenge(): void
    {
        $victim = $this->victim();
        $this->enableTwoFactor([
            TwoFactorAuthentication::EMAIL,
            TwoFactorAuthentication::SMS,
            TwoFactorAuthentication::AUTH_APP,
        ]);

        $code = $this->currentDerivedCode($victim->uuid, $victim->username, false);

        $this->actingAs($victim, 'web')
            ->get('/')
            ->assertRedirect(route('2fa'));

        $this->actingAs($victim, 'web')
            ->post('/2fa/validate', ['code' => $code]);

        $this->assertNotSame(true, session(TwoFactorAuthController::TFA_VALIDATED));
        $this->actingAs($victim, 'web')
            ->get('/')
            ->assertRedirect(route('2fa'));
    }

    public function testSubmittedAuthenticatorCodeFromPublicInputsDoesNotClearTheChallenge(): void
    {
        $victim = $this->victim();
        $this->enableTwoFactor([TwoFactorAuthentication::AUTH_APP]);

        $code = $this->currentDerivedCode($victim->uuid, $victim->username, true);

        $this->actingAs($victim, 'web')
            ->get('/')
            ->assertRedirect(route('2fa'));

        $this->actingAs($victim, 'web')
            ->post('/2fa/validate', ['code' => $code]);

        $this->assertNotSame(true, session(TwoFactorAuthController::TFA_VALIDATED));
        $this->actingAs($victim, 'web')
            ->get('/')
            ->assertRedirect(route('2fa'));
    }

    public function testEmailAndSmsDispatchDoesNotUseTheDerivedCode(): void
    {
        $victim = $this->victim();
        $this->enableTwoFactor([
            TwoFactorAuthentication::EMAIL,
            TwoFactorAuthentication::SMS,
        ]);
        $derived = $this->currentDerivedCode($victim->uuid, $victim->username, false);

        $this->assertSame(
            [TwoFactorAuthentication::EMAIL, TwoFactorAuthentication::SMS],
            $victim->getValid2FAPreferences()
        );

        Notification::fake();
        try {
            (new TwoFactorAuthentication())->sendCode($victim);
        } catch (Throwable $error) {
            // Carrier delivery can fail after the code is chosen. The email
            // notification receives that same code before the SMS attempt.
            if (Notification::sent($victim, TwoFactorAuthNotification::class)->isEmpty()) {
                throw $error;
            }
        }

        $notification = Notification::sent($victim, TwoFactorAuthNotification::class)->first();
        $this->assertInstanceOf(TwoFactorAuthNotification::class, $notification);
        $body = implode("\n", $notification->toMail($victim)->introLines);
        $this->assertFalse(str_contains($body, $derived));
    }

    public function testAuthenticatorQrDoesNotEncodeTheDerivedSecret(): void
    {
        $victim = $this->victim();
        $derivedImage = $this->derivedProvisioningImage($victim);

        $fromService = base64_decode((new TwoFactorAuthentication())->generateQr($victim));
        $this->assertNotSame($derivedImage, $fromService);
    }

    public function testAuthenticatorQrEndpointDoesNotEncodeTheDerivedSecret(): void
    {
        $victim = $this->victim();
        $derivedImage = $this->derivedProvisioningImage($victim);

        $response = $this->actingAs($victim, 'web')->get('/2fa/auth_app_qr');
        $response->assertOk();
        $fromHttp = base64_decode($response->viewData('qrCode'));
        $this->assertNotSame($derivedImage, $fromHttp);
    }

    private function victim(): User
    {
        $user = User::factory()->create([
            'username' => 'twofa.victim',
            'is_administrator' => true,
            'cell' => '15555550100',
            'preferences_2fa' => null,
            'force_change_password' => 0,
        ]);

        return $user->refresh();
    }

    private function enableTwoFactor(array $methods): void
    {
        config([
            'password-policies.2fa_enabled' => true,
            'password-policies.2fa_method' => $methods,
        ]);
    }

    private function ensurePermission(string $name): void
    {
        Permission::updateOrCreate(
            ['name' => $name],
            ['title' => $name, 'group' => 'Tests']
        );
        Cache::forget('permissions');
        $this->initializePermissions(false);
    }

    private function derivedSecret(string $uuid, string $username): string
    {
        return trim(Base32::encodeUpper($uuid . '_' . $username), '=');
    }

    private function derivedTotp(string $uuid, string $username, bool $authenticatorApp): TOTP
    {
        $otp = TOTP::createFromSecret($this->derivedSecret($uuid, $username));
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($username);
        if (!$authenticatorApp) {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

        return $otp;
    }

    private function currentDerivedCode(string $uuid, string $username, bool $authenticatorApp): string
    {
        $otp = $this->derivedTotp($uuid, $username, $authenticatorApp);
        if ($otp->expiresIn() < 2) {
            sleep(2);
            $otp = $this->derivedTotp($uuid, $username, $authenticatorApp);
        }

        return $otp->now();
    }

    private function derivedProvisioningImage(User $user): string
    {
        $otp = $this->derivedTotp($user->uuid, $user->username, true);
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(250),
                new SvgImageBackEnd()
            )
        );

        return $writer->writeString($otp->getProvisioningUri());
    }
}
