<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Mail\TestEmailServer;
use ProcessMaker\Models\User;
use ProcessMaker\TwoFactorAuthentication;
use Twilio\Rest\Client;

class TwoFactorAuthController extends Controller
{
    private $twoFactorAuthentication;

    const TFA_ERROR = '2fa-error';

    const TFA_MESSAGE = '2fa-message';

    const TFA_AUTH_APP = '2fa-auth-app';

    const TFA_VALIDATED = '2fa-validated';

    public function __construct()
    {
        $this->twoFactorAuthentication = new TwoFactorAuthentication();
    }

    private function reloadUser(?User $user): ?User
    {
        return $user?->fresh() ?? $user;
    }

    public function displayTwoFactorAuthForm(Request $request)
    {
        try {
            $user = $this->reloadUser($request->user());

            if (empty($user) || !config('password-policies.2fa_enabled', false)) {
                return redirect()->route('login');
            }

            $this->twoFactorAuthentication->rememberChallengeMethodsSnapshot($user);
            if ($this->twoFactorAuthentication->shouldShowQrForFirstTime($user)) {
                return redirect()->route('2fa.auth_app_qr');
            }

            $this->sendTwoFactorCodeForDisplay($user);

            if ($this->twoFactorAuthentication->isInAuthenticatorEnrollmentPhase($user)) {
                session()->put(
                    self::TFA_MESSAGE,
                    __('Enter the 6-digit code from your authenticator app.')
                );
            } else {
                $methodsNames = $this->twoFactorAuthentication->friendlyMethodsNames($user);
                $message = __('Enter the security code from :methods. If incorrect, please retry with the latest code provided.',
                    ['methods' => $methodsNames]);
                session()->put(self::TFA_MESSAGE, $message);
            }
        } catch (Exception $error) {
            session()->put(self::TFA_ERROR, $error->getMessage());
        }

        return view('auth.2fa.otp');
    }

    public function validateTwoFactorAuthCode(Request $request)
    {
        // Get current user and code
        $user = $request->user();
        $code = $request->get('code');

        // If not user not authenticated, redirect to login page
        if (empty($user)) {
            return redirect()->route('login');
        }

        // If empty code return error message
        if (empty($code)) {
            // Set error message
            session()->put(self::TFA_ERROR, __('Invalid code.'));

            // Return to 2fa page
            return redirect()->route('2fa');
        }

        $this->twoFactorAuthentication->rememberChallengeMethodsSnapshot($user);

        $validated = $this->twoFactorAuthentication->validateCode($user, $code);

        session()->put(
            self::TFA_VALIDATED,
            $this->twoFactorAuthentication->clearsWebTwoFactorGate($user, $code, $validated)
        );

        if ($validated) {
            session()->remove(self::TFA_MESSAGE);
            session()->remove(self::TFA_ERROR);
            session()->remove(self::TFA_AUTH_APP);

            $user = $this->reloadUser($user);
            $route = 'login';
            if (strlen($code) !== 6 && $this->twoFactorAuthentication->mayEnrollNewAuthenticator($user)) {
                $route = $this->twoFactorAuthentication->sessionHasPendingAuthenticatorForUser($user)
                    ? '2fa'
                    : '2fa.auth_app_qr';

                if ($route === '2fa') {
                    session()->put(
                        self::TFA_MESSAGE,
                        __('Enter the 6-digit code from your authenticator app.')
                    );
                }
            }
        } else {
            // Set error message
            session()->put(self::TFA_ERROR, __('Invalid code.'));

            // Return to 2fa page
            $route = '2fa';
        }

        return redirect()->route($route);
    }

    public function sendCode(Request $request)
    {
        $user = $this->reloadUser($request->user());

        if ($this->twoFactorAuthentication->isInAuthenticatorEnrollmentPhase($user)) {
            return redirect()->route('2fa');
        }

        try {
            $this->twoFactorAuthentication->sendCode($user);
        } catch (Exception $error) {
            session()->put(self::TFA_ERROR, $error->getMessage());
        }

        return redirect()->route('2fa');
    }

    public function displayAuthAppQr(Request $request)
    {
        $user = $this->reloadUser($request->user());

        if (empty($user) || !config('password-policies.2fa_enabled', false)) {
            return redirect()->route('login');
        }

        $this->twoFactorAuthentication->rememberChallengeMethodsSnapshot($user);
        $methods = $this->twoFactorAuthentication->userPreferredMethods($user);

        if (!in_array(TwoFactorAuthentication::AUTH_APP, $methods, true)
            || !$this->twoFactorAuthentication->mayEnrollNewAuthenticator($user)
        ) {
            return redirect()->route('2fa');
        }

        $qrCode = $this->twoFactorAuthentication->generateQr($user);

        return view('auth.2fa.auth_app_qr', compact('qrCode'));
    }

    private function sendTwoFactorCodeForDisplay(User $user): void
    {
        if ($this->twoFactorAuthentication->isInAuthenticatorEnrollmentPhase($user)) {
            return;
        }

        if (!session()->has(self::TFA_ERROR) && !session()->has(self::TFA_MESSAGE)) {
            $this->twoFactorAuthentication->sendCode($user);

            return;
        }

        $methods = $this->twoFactorAuthentication->userPreferredMethods($user);
        $needsProvisioning = in_array(TwoFactorAuthentication::EMAIL, $methods, true)
            || in_array(TwoFactorAuthentication::SMS, $methods, true)
            ? empty($user->two_factor_delivery_secret)
            : empty($user->two_factor_secret);

        if (!session()->has(self::TFA_MESSAGE) || $needsProvisioning) {
            $this->twoFactorAuthentication->sendCode($user);
        }
    }

    public function testSettings(Request $request)
    {
        $enabled = $request->json('enabled');
        $message = [
            'status' => 'success',
            'message' => __('Configuration tested successfully.'),
        ];
        $status = 200;

        //The Two Step Method should be seleceted
        if (count($enabled) === 0) {
            $message = [
                'status' => 'error',
                'message' => __('The two-step method must be selected.'),
            ];
            $status = 500;
        }

        // Test Email Server, send email to current user
        if (in_array('By email', $enabled)) {
            $testEmailServer = $this->testEmailServer();
            if ($testEmailServer !== true) {
                $message = json_decode($testEmailServer->getContent(), true);
                $status = $testEmailServer->getStatusCode() ?? 500;
            }
        }

        // Test SMS Server
        if (in_array('By message to phone number', $enabled)) {
            $testSmsServer = $this->testSmsServer();
            if ($testSmsServer !== true) {
                $message = json_decode($testSmsServer->getContent(), true);
                $status = $testSmsServer->getStatusCode() ?? 500;
            }
        }

        return response()->json($message, $status);
    }

    private function testEmailServer()
    {
        try {
            $user = Auth::user();
            Mail::to($user)->send(new TestEmailServer);
        } catch (Exception $error) {
            return response()->json([
                'status' => 'error',
                'message' => __('Unable to send email. Please check your email server settings.'),
            ], 500);
        }

        return true;
    }

    private function testSmsServer()
    {
        try {
            $user = Auth::user();
            // Get config parameters for Twilio
            $sid = config('twilio.sid');
            $token = config('twilio.token');
            $from = config('twilio.active_phone_number');

            // Format the number to send the code
            $to = '+' . ltrim($user->cell, '+');

            // Build body
            $body = $user->username . PHP_EOL . PHP_EOL;
            $body .= __('This is a test') . PHP_EOL . PHP_EOL;

            // Send SMS using Twilio SDK
            $twilio = new Client($sid, $token);
            $twilio->messages->create($to,
                [
                    'from' => $from,
                    'body' => $body,
                ],
            );
        } catch (Exception $error) {
            return response()->json([
                'status' => 'error',
                'message' => __('Unable to send SMS. Please check your cell number and SMS server settings.'),
            ], 500);
        }

        return true;
    }

    /**
     * Check if 2FA is enabled for the current user based on the groups he belongs to
     *
     * @return bool
     */
    public static function check2faByGroups()
    {
        try {
            $user = Auth::user();

            return $user->in2FAGroupOrIndependent();
        } catch (Exception $e) {
            session()->put(self::TFA_ERROR, $e->getMessage());
        }

        return false;
    }
}
