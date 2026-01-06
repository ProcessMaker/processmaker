<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class ResetPasswordController extends Controller implements HasMiddleware
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

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/password/success';

    public static function middleware(): array
    {
        return [
            'guest',
        ];
    }

    /**
     * Show the application's reset form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showResetForm(Request $request, $token)
    {
        $username = User::where('email', $request->input('email'))->firstOrFail()->username;

        return view('auth.passwords.reset', compact('username', 'token'));
    }
}
