<?php

namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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

        if ($user->status === 'INACTIVE') {
            return redirect()->route('password.request')
                ->withErrors(['email' => __('passwords.inactive')]);
        }

        return view('auth.passwords.reset', [
            'username' => $user->username,
            'token' => $token,
            'email' => $request->input('email'),
        ]);
    }

    /**
     * Reset the given user's password.
     * Blocked or inactive users cannot reset their password.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $user = User::where('email', $request->input('email'))
            ->where('username', $request->input('username'))
            ->first();

        if ($user && $user->status === 'BLOCKED') {
            return $this->sendResetFailedResponse($request, 'passwords.blocked');
        }

        if ($user && $user->status === 'INACTIVE') {
            return $this->sendResetFailedResponse($request, 'passwords.inactive');
        }

        if (!$user) {
            return redirect()->back()
                ->withInput($request->only('email', 'username'))
                ->withErrors(['email' => __('passwords.account_not_found')]);
        }

        return $this->performPasswordReset($request);
    }

    /**
     * Get the password reset validation rules.
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'username' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Get the password reset credentials from the request.
     * Include username so the broker resolves the same user as email+username (not email alone).
     */
    protected function credentials(Request $request): array
    {
        return $request->only(
            'email',
            'username',
            'password',
            'password_confirmation',
            'token'
        );
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }

        return redirect()->back()
            ->withInput($request->only('email', 'username'))
            ->withErrors(['email' => trans($response)]);
    }
}
