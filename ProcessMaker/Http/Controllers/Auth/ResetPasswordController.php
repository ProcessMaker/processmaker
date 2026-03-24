<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords {
        reset as protected performPasswordReset;
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/password/success';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application's reset form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showResetForm(Request $request, $token)
    {
        $user = User::where('email', $request->input('email'))->firstOrFail();

        if ($user->status === 'BLOCKED') {
            return redirect()->route('password.request')
                ->withErrors(['email' => __('passwords.blocked')]);
        }

        return view('auth.passwords.reset', [
            'username' => $user->username,
            'token' => $token,
            'email' => $request->input('email'),
        ]);
    }

    /**
     * Reset the given user's password.
     * Blocked users cannot reset their password.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->status === 'BLOCKED') {
            return $this->sendResetFailedResponse($request, 'passwords.blocked');
        }

        return $this->performPasswordReset($request);
    }
}
