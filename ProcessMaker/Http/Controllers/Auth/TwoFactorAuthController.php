<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Exception;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\TwoFactorAuthentication;

class TwoFactorAuthController extends Controller
{
    private $twoFactorAuthentication;

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
            if (!session()->has('2fa-error')) {
                $this->twoFactorAuthentication->sendCode($user);
            }

            // Set informative message
            session()->put('2fa-message', _('Enter the security code we sent you.'));
        } catch (Exception $error) {
            session()->put('2fa-error', $error->getMessage());
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
            session()->put('2fa-error', _('Invalid code.'));

            // Return to 2fa page
            return redirect()->route('2fa');
        }

        // Validate code
        $validated = $this->twoFactorAuthentication->validateCode($user, $code);

        // Store validation status
        session()->put('2fa-validated', $validated);

        if ($validated) {
            // Remove 2fa values in session
            session()->remove('2fa-message');
            session()->remove('2fa-error');
            session()->remove('2fa-auth-app');

            // Success
            return redirect()->route('login');
        } else {
            // Set error message
            session()->put('2fa-error', _('Invalid code.'));

            // Return to 2fa page
            return redirect()->route('2fa');
        }
    }
}
