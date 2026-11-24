<?php

namespace ProcessMaker;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwoFactorAuthentication
{
    const EMAIL = 'By email';

    const SMS = 'By message to phone number';

    const AUTH_APP = 'Authenticator App';

    const ERROR_INVALID_TO_NUMBER = 21211;

    public const PENDING_AUTHENTICATOR_SECRET = '2fa-pending-authenticator-secret';

    public const PENDING_AUTHENTICATOR_USER_ID = '2fa-pending-authenticator-user-id';

    public const DELIVERY_STEP_UP = '2fa-delivery-step-up';

    public const CHALLENGE_METHODS_SNAPSHOT = '2fa-challenge-methods';

    public const CHALLENGE_METHODS_USER_ID = '2fa-challenge-methods-user-id';

    public const CHALLENGE_DELIVERY_EMAIL = '2fa-challenge-delivery-email';

    public const CHALLENGE_DELIVERY_CELL = '2fa-challenge-delivery-cell';

    public const CHALLENGE_DELIVERY_USER_ID = '2fa-challenge-delivery-user-id';

    public function sendCode(User $user): void
    {
        // Get methods to send the code
        $methods = $user->getValid2FAPreferences();
        $deliveryUser = $this->userForDeliveryChallenge($user);

        if (in_array(self::EMAIL, $methods) || in_array(self::SMS, $methods)) {
            // Get code
            $code = $this->getCodeForEmailSms($user);

            // Send OTP
            if (in_array(self::EMAIL, $methods)) {
                Notification::send($deliveryUser, new TwoFactorAuthNotification($deliveryUser, $code));
            }
            if (in_array(self::SMS, $methods)) {
                $this->sendSms($deliveryUser, $code);
            }
        } elseif (in_array(self::AUTH_APP, $methods)) {
            session()->put('2fa-auth-app', true);
        }
    }

    private function getUserDeliveryTotpSecret(User $user): string
    {
        if (!empty($user->two_factor_delivery_secret)) {
            return $user->two_factor_delivery_secret;
        }

        $secret = TOTP::generate()->getSecret();

        if ($user->exists) {
            $user->two_factor_delivery_secret = $secret;
            $user->saveQuietly();
        } else {
            $user->setAttribute('two_factor_delivery_secret', $secret);
        }

        return $secret;
    }

    private function resolveAuthenticatorSecret(User $user): ?string
    {
        if (!empty($user->two_factor_secret)) {
            return $user->two_factor_secret;
        }

        if (!$this->sessionHasPendingAuthenticatorForUser($user)) {
            return null;
        }

        return session()->get(self::PENDING_AUTHENTICATOR_SECRET);
    }

    public function sessionHasPendingAuthenticatorForUser(User $user): bool
    {
        if (!session()->exists(self::PENDING_AUTHENTICATOR_SECRET)) {
            return false;
        }

        $pendingUserId = session()->get(self::PENDING_AUTHENTICATOR_USER_ID);

        return $pendingUserId === null || (int) $pendingUserId === (int) $user->id;
    }

    public function forgetPendingAuthenticatorEnrollment(): void
    {
        session()->forget(self::PENDING_AUTHENTICATOR_SECRET);
        session()->forget(self::PENDING_AUTHENTICATOR_USER_ID);
    }

    public function needsAuthenticatorEnrollment(User $user): bool
    {
        return empty($user->two_factor_secret)
            && in_array(self::AUTH_APP, $this->challengeMethods($user), true);
    }

    public function authenticatorEnrollmentRequiredForGate(User $user): bool
    {
        return $this->needsAuthenticatorEnrollment($user);
    }

    public function isInAuthenticatorEnrollmentPhase(User $user): bool
    {
        if (!$this->needsAuthenticatorEnrollment($user)) {
            return false;
        }

        if ($this->sessionHasPendingAuthenticatorForUser($user)) {
            return true;
        }

        return $this->hasCompletedDeliveryStepUp()
            && $this->mayEnrollNewAuthenticator($user);
    }

    public function shouldShowQrForFirstTime(User $user): bool
    {
        if (!$this->needsAuthenticatorEnrollment($user)) {
            return false;
        }

        if ($this->sessionHasPendingAuthenticatorForUser($user)) {
            return false;
        }

        if (!$this->challengeRequiresDeliveryStepUp()) {
            return true;
        }

        return $this->hasCompletedDeliveryStepUp()
            && $this->mayEnrollNewAuthenticator($user);
    }

    public function clearsWebTwoFactorGate(User $user, string $code, bool $codeValid): bool
    {
        if (!$codeValid) {
            return false;
        }

        if (strlen($code) === 6) {
            return true;
        }

        return !$this->authenticatorEnrollmentRequiredForGate($user);
    }

    public function challengeMethods(User $user): array
    {
        $snapshot = session()->get(self::CHALLENGE_METHODS_SNAPSHOT);
        $snapshotUserId = session()->get(self::CHALLENGE_METHODS_USER_ID);

        // Use snapshot only if it exists, is valid, and belongs to this user
        if (is_array($snapshot) && $snapshot !== []) {
            if ($snapshotUserId === null || (int) $snapshotUserId === (int) $user->id) {
                return $snapshot;
            }
        }

        return $user->getValid2FAPreferences();
    }

    public function userPreferredMethods(User $user): array
    {
        return $user->getValid2FAPreferences();
    }

    public function rememberChallengeMethodsSnapshot(User $user): void
    {
        // Check if snapshot exists AND belongs to this user
        if (session()->exists(self::CHALLENGE_METHODS_SNAPSHOT)) {
            $snapshotUserId = session()->get(self::CHALLENGE_METHODS_USER_ID);
            if ($snapshotUserId === null || (int) $snapshotUserId === (int) $user->id) {
                return; // Snapshot exists for this user
            }
            // Snapshot belongs to different user, clear it
            $this->forgetChallengeSnapshot();
        }

        $methods = $user->getValid2FAPreferences();

        session()->put(self::CHALLENGE_METHODS_SNAPSHOT, $methods);
        session()->put(self::CHALLENGE_METHODS_USER_ID, $user->id);
        session()->put(self::CHALLENGE_DELIVERY_EMAIL, $user->email);
        session()->put(self::CHALLENGE_DELIVERY_CELL, $user->cell);
        session()->put(self::CHALLENGE_DELIVERY_USER_ID, $user->id);
    }

    private function userForDeliveryChallenge(User $user): User
    {
        $email = session()->get(self::CHALLENGE_DELIVERY_EMAIL);
        $cell = session()->get(self::CHALLENGE_DELIVERY_CELL);
        if (!session()->exists(self::CHALLENGE_DELIVERY_EMAIL) && !session()->exists(self::CHALLENGE_DELIVERY_CELL)) {
            return $user;
        }

        $snapshotUserId = session()->get(self::CHALLENGE_DELIVERY_USER_ID);
        if ($snapshotUserId !== null && (int) $snapshotUserId !== (int) $user->id) {
            return $user;
        }

        $deliveryUser = $user->replicate();
        $deliveryUser->exists = true;
        $deliveryUser->id = $user->id;
        if (session()->exists(self::CHALLENGE_DELIVERY_EMAIL)) {
            $deliveryUser->email = $email;
        }
        if (session()->exists(self::CHALLENGE_DELIVERY_CELL)) {
            $deliveryUser->cell = $cell;
        }

        return $deliveryUser;
    }

    public function challengeRequiresDeliveryStepUp(): bool
    {
        $methods = session()->get(self::CHALLENGE_METHODS_SNAPSHOT, []);

        return in_array(self::EMAIL, $methods, true)
            || in_array(self::SMS, $methods, true);
    }

    private function hasCompletedDeliveryStepUp(): bool
    {
        return session()->get(self::DELIVERY_STEP_UP, false);
    }

    public function mayEnrollNewAuthenticator(User $user): bool
    {
        if (!$this->needsAuthenticatorEnrollment($user)) {
            return false;
        }

        if (!$this->challengeRequiresDeliveryStepUp()) {
            return true;
        }

        return $this->hasCompletedDeliveryStepUp();
    }

    private function configureOtp(TOTP $otp, User $user, bool $forGoogleAuthApp): TOTP
    {
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($user->username);

        if (!$forGoogleAuthApp) {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

        return $otp;
    }

    private function createOtpInstance(User $user, bool $forGoogleAuthApp = false)
    {
        $secret = $forGoogleAuthApp
            ? $this->resolveAuthenticatorSecret($user)
            : $this->getUserDeliveryTotpSecret($user);

        // Config OTP
        return $this->configureOtp(TOTP::createFromSecret($secret), $user, $forGoogleAuthApp);
    }

    private function getCodeForEmailSms(User $user): string
    {
        // Create OTP instance
        $otp = $this->createOtpInstance($user);

        // Return current code
        return $otp->now();
    }

    public function validateCode(User $user, string $code)
    {
        $methods = $this->challengeMethods($user);

        if (strlen($code) === 6) {
            return $this->verifyAuthenticatorCode($user, $code, $methods);
        }

        if (!$this->hasDeliveryMethod($methods) || empty($user->two_factor_delivery_secret)) {
            return false;
        }

        $otp = $this->createOtpInstance($user, false);
        $valid = $otp->verify($code);
        if ($valid) {
            session()->put(self::DELIVERY_STEP_UP, true);
        }

        return $valid;
    }

    private function verifyAuthenticatorCode(User $user, string $code, array $methods): bool
    {
        $authSecret = $this->resolveAuthenticatorSecret($user);
        $cannotUseAuthenticator = !in_array(self::AUTH_APP, $methods, true) || $authSecret === null;
        $cannotEnroll = empty($user->two_factor_secret) && !$this->mayEnrollNewAuthenticator($user);
        if ($cannotUseAuthenticator || $cannotEnroll) {
            return false;
        }

        $otp = $this->createOtpInstance($user, true);
        $valid = $otp->verify($code);
        if ($valid && empty($user->two_factor_secret)) {
            $user->two_factor_secret = $authSecret;
            $user->saveQuietly();
            $this->forgetPendingAuthenticatorEnrollment();
        }

        return $valid;
    }

    private function hasDeliveryMethod(array $methods): bool
    {
        return in_array(self::EMAIL, $methods, true) || in_array(self::SMS, $methods, true);
    }

    /**
     * @param User $user
     * @param string $code
     *
     * @throws TwilioException
     */
    private function sendSms(User $user, string $code)
    {
        // Get config parameters for Twilio
        $sid = config('twilio.sid');
        $token = config('twilio.token');
        $from = config('twilio.active_phone_number');

        // Format the number to send the code
        $to = '+' . ltrim($user->cell, '+');

        // Build body
        $body = $user->username . PHP_EOL . PHP_EOL;
        $body .= __('This is your security code: :code', ['code' => $code]) . PHP_EOL . PHP_EOL;
        $body .= __('Regards') . PHP_EOL;
        $body .= 'ProcessMaker';

        // Send SMS using Twilio SDK
        $twilio = new Client($sid, $token);
        try {
            $twilio->messages->create($to,
                [
                    'from' => $from,
                    'body' => $body,
                ]
            );
        } catch (TwilioException $error) {
            // Check if the error code is for a invalid "to" number
            if ($error->getCode() === self::ERROR_INVALID_TO_NUMBER) {
                // Get methods to send the code
                $methods = $user->getValid2FAPreferences();

                // Get another methods
                $otherMethods = array_diff($methods, [self::SMS]);

                // Set message
                $message = __(
                    'Invalid phone number. Please verify and update your phone number in your account settings.'
                );

                // Add complementary information if is needed
                if (!empty($otherMethods)) {
                    $otherMethods = $this->friendlyMethodsNames($user, $otherMethods);
                    $message .= ' ';
                    $message .= __('You can also authenticate by :otherMethods.',
                        ['otherMethods' => $otherMethods]);
                }

                // Change to a friendly error message
                $error = new TwilioException($message, self::ERROR_INVALID_TO_NUMBER);
            }
            throw $error;
        }
    }

    public function generateQr(User $user)
    {
        $secret = session()->get(self::PENDING_AUTHENTICATOR_SECRET);
        if (empty($secret) || !$this->sessionHasPendingAuthenticatorForUser($user)) {
            $secret = TOTP::generate()->getSecret();
            session()->put(self::PENDING_AUTHENTICATOR_SECRET, $secret);
            session()->put(self::PENDING_AUTHENTICATOR_USER_ID, $user->id);
        }

        $otp = $this->configureOtp(TOTP::createFromSecret($secret), $user, true);

        $g2faUrl = $otp->getProvisioningUri();

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(250),
                new SvgImageBackEnd()
            )
        );

        return base64_encode($writer->writeString($g2faUrl));
    }

    public function selfServiceUpdateRequiresTwoFactorCompletion(User $apiUser): bool
    {
        if (!$this->isTwoFactorEnabled()) {
            return false;
        }

        $webUserId = \Illuminate\Support\Facades\Auth::guard('web')->id();
        if ($webUserId === null || (int) $webUserId !== (int) $apiUser->id) {
            return true;
        }

        if ($this->userHasSsoBinding($apiUser) && $this->hasSsoDriverSession()) {
            return false;
        }

        return !session()->get(Http\Controllers\Auth\TwoFactorAuthController::TFA_VALIDATED, false);
    }

    private function isTwoFactorEnabled(): bool
    {
        return config('password-policies.2fa_enabled', false)
            && !empty(config('password-policies.2fa_method', []))
            && Http\Controllers\Auth\TwoFactorAuthController::check2faByGroups();
    }

    private function userHasSsoBinding(User $user): bool
    {
        if (isset($user->meta?->authenticationType)
            && $user->meta->authenticationType === 'ldap') {
            return true;
        }

        $ssoUserClass = 'ProcessMaker\Package\Auth\Models\SsoUser';
        if (class_exists($ssoUserClass)) {
            return $ssoUserClass::where('user_id', $user->id)->exists();
        }

        return false;
    }

    private function hasSsoDriverSession(): bool
    {
        return session()->has('sso_driver');
    }

    public function friendlyMethodsNames(User $user, array $enabledMethods = [])
    {
        // Define the friendly names for each method
        $friendlyNames = [
            self::EMAIL => __('Email'),
            self::SMS => __('SMS'),
            self::AUTH_APP => __('Google Authenticator'),
        ];

        // Get enabled methods to send the code
        if (empty($enabledMethods)) {
            $enabledMethods = $this->userPreferredMethods($user);
        }

        // Return the friendly names for enabled methods
        $methods = array_map(function ($method) use ($friendlyNames) {
            return $friendlyNames[$method] ?? $method;
        }, $enabledMethods);

        // Build final string
        if (count($methods) > 1) {
            $lastMethod = array_pop($methods);
            $methods = implode(', ', $methods) . ' ' . __('or') . ' ' . $lastMethod;
        } else {
            $methods = array_pop($methods);
        }

        return $methods;
    }

    private function forgetChallengeSnapshot(): void
    {
        session()->forget(self::CHALLENGE_METHODS_SNAPSHOT);
        session()->forget(self::CHALLENGE_METHODS_USER_ID);
        session()->forget(self::CHALLENGE_DELIVERY_EMAIL);
        session()->forget(self::CHALLENGE_DELIVERY_CELL);
        session()->forget(self::CHALLENGE_DELIVERY_USER_ID);
    }
}
