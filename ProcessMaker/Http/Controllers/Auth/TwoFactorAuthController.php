<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\TwoFactorAuthentication;

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

    public function displayTwoFactorAuthForm(Request $request)
    {
        try {
            // Get current user
            $user = $request->user();

            // If not user not authenticated, redirect to login page
            if (empty($user)) {
                return redirect()->route('login');
            }

            // Send code
            if (!session()->has(self::TFA_ERROR) && !session()->has(self::TFA_MESSAGE)) {
                $this->twoFactorAuthentication->sendCode($user);
            } else {
                if (!session()->has(self::TFA_MESSAGE)) {
                    $this->twoFactorAuthentication->sendCode($user);
                }
            }

            // Set informative message
            session()->put(self::TFA_MESSAGE, _('Enter the security code we sent you.'));
        } catch (Exception $error) {
            session()->put(self::TFA_ERROR, $error->getMessage());
        }

        // Display view
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
            session()->put(self::TFA_ERROR, _('Invalid code.'));

            // Return to 2fa page
            return redirect()->route('2fa');
        }

        // Validate code
        $validated = $this->twoFactorAuthentication->validateCode($user, $code);

        // Store validation status
        session()->put(self::TFA_VALIDATED, $validated);

        if ($validated) {
            // Remove 2fa values in session
            session()->remove(self::TFA_MESSAGE);
            session()->remove(self::TFA_ERROR);
            session()->remove(self::TFA_AUTH_APP);

            // Success
            $route = 'login';
        } else {
            // Set error message
            session()->put(self::TFA_ERROR, _('Invalid code.'));

            // Return to 2fa page
            $route = '2fa';
        }

        return redirect()->route($route);
    }

    public function sendCode(Request $request)
    {
        // Get current user
        $user = $request->user();

        // Send the code
        $this->twoFactorAuthentication->sendCode($user);

        // Return to 2fa page
        return redirect()->route('2fa');
    }

    public function displayAuthAppQr(Request $request)
    {
        // Get current user
        $user = $request->user();

        // If not user not authenticated, redirect to login page
        if (empty($user)) {
            return redirect()->route('login');
        }

        // Generate QR code
        $qrCode = $this->twoFactorAuthentication->generateQr($user);

        // Display view
        return view('auth.2fa.auth_app_qr', compact('qrCode'));
    }
}
