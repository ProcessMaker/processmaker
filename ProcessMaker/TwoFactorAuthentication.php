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

    public const DELIVERY_STEP_UP = '2fa-delivery-step-up';

    public const CHALLENGE_METHODS_SNAPSHOT = '2fa-challenge-methods';

    public function sendCode(User $user): void
    {
        // Get methods to send the code
        $methods = $user->getValid2FAPreferences();

        if (in_array(self::EMAIL, $methods) || in_array(self::SMS, $methods)) {
            // Get code
            $code = $this->getCodeForEmailSms($user);

            // Send OTP
            if (in_array(self::EMAIL, $methods)) {
                Notification::send($user, new TwoFactorAuthNotification($user, $code));
            }
            if (in_array(self::SMS, $methods)) {
                $this->sendSms($user, $code);
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

        $pending = session()->get(self::PENDING_AUTHENTICATOR_SECRET);

        return !empty($pending) ? $pending : null;
    }

    public function rememberChallengeMethodsSnapshot(User $user): void
    {
        if (session()->has(self::CHALLENGE_METHODS_SNAPSHOT)) {
            return;
        }

        session()->put(
            self::CHALLENGE_METHODS_SNAPSHOT,
            $user->getValid2FAPreferences()
        );
    }

    public function challengeRequiresDeliveryStepUp(): bool
    {
        $methods = session()->get(self::CHALLENGE_METHODS_SNAPSHOT, []);

        return in_array(self::EMAIL, $methods, true)
            || in_array(self::SMS, $methods, true);
    }

    public function mayEnrollNewAuthenticator(User $user): bool
    {
        $methods = $user->getValid2FAPreferences();
        if (!empty($user->two_factor_secret) || !in_array(self::AUTH_APP, $methods, true)) {
            return false;
        }

        if (!$this->challengeRequiresDeliveryStepUp()) {
            return true;
        }

        return session()->get(self::DELIVERY_STEP_UP, false);
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
        $methods = $user->getValid2FAPreferences();

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
            session()->forget(self::PENDING_AUTHENTICATOR_SECRET);
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
        if (empty($secret)) {
            $secret = TOTP::generate()->getSecret();
            session()->put(self::PENDING_AUTHENTICATOR_SECRET, $secret);
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
            $enabledMethods = $user->getValid2FAPreferences();
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
}
