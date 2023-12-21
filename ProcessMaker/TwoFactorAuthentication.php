<?php

namespace ProcessMaker;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\TwoFactorAuthNotification;
use Twilio\Rest\Client;

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
                $this->sendSms($user, $code);
            }
        } elseif (in_array(self::AUTH_APP, $methods)) {
            session()->put('2fa-auth-app', true);
        }
    }

    private function generateSecret(User $user): string
    {
        $base = $user->uuid . '_' . $user->username;
        return trim(Base32::encodeUpper($base), '=');
    }

    private function createOtpInstance(User $user, bool $forGoogleAuthApp = false)
    {
        // Get secret
        $secret = $this->generateSecret($user);

        // Config OTP
        $otp = TOTP::createFromSecret($secret);
        $otp->setIssuer('ProcessMaker');
        $otp->setLabel($user->username);

        if (!$forGoogleAuthApp) {
            $otp->setDigest('sha512');
            $otp->setDigits(8);
            $otp->setPeriod(300);
        }

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
        // The code is for Google Authenticator app?
        $forGoogleAuthApp = strlen($code) === 6;

        // Create OTP instance
        $otp = $this->createOtpInstance($user, $forGoogleAuthApp);

        // Validate code
        return $otp->verify($code);
    }

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
        $body .= _('Regards') . PHP_EOL;
        $body .= 'ProcessMaker';

        // Send SMS using Twilio SDK
        $twilio = new Client($sid, $token);
        $twilio->messages->create($to,
            [
                'from' => $from,
                'body' => $body
            ]
        );
    }

    public function generateQr(User $user)
    {
        $otp = $this->createOtpInstance($user, true);

        $g2faUrl = $otp->getProvisioningUri();

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(250),
                new SvgImageBackEnd()
            )
        );

        $qrCode = base64_encode($writer->writeString($g2faUrl));

        return $qrCode;
    }
}
