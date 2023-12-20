<?php

namespace ProcessMaker;

use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;

class TwoFactorAuthentication
{
    const EMAIL = 'By email';
    const SMS = 'By message to phone number';
    const AUTH_APP = 'Authenticator App';

    public function sendCode(User $user): void
    {
        // Get methods to send the code
        $methods = config('password-policies.2fa_method', []);

        if (in_array(self::EMAIL, $methods) || in_array(self::SMS, $methods)) {
            // Get code
            $code = $this->getCodeForEmailSms($user);

            // Send OTP
            if (in_array(self::EMAIL, $methods)) {
                Notification::send($user, new TwoFactorAuthNotification($user, $code));
            }
            if (in_array(self::SMS, $methods)) {
                // TO DO: Send SMS
            }
        } elseif (in_array(self::AUTH_APP, $methods)) {
            session()->put('2fa-auth-app', true);
        }
    }

    private function generateSecret(User $user): string
    {
        $base = $user->uuid . '_' . $user->username;
        $secret = trim(Base32::encodeUpper($base), '=');
        return $secret;
    }

    private function createOtpInstance(User $user)
    {
        // Get secret
        $secret = $this->generateSecret($user);

        // Config OTP
        $otp = TOTP::createFromSecret($secret);
        $otp->setDigest('sha512');
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($user->username);
        $otp->setDigits(10);
        $otp->setPeriod(300);

        return $otp;
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
        // Create OTP instance
        $otp = $this->createOtpInstance($user);

        // Validate code
        return $otp->verify($code);
    }
}
