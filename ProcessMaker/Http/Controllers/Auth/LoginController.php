<?php
namespace ProcessMaker\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use ProcessMaker\Http\Controllers\Controller;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        sendLoginResponse as protected traitSendLoginResponse;
        sendFailedLoginResponse as protected traitSendFailedLoginResponse;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }
    
    public function sendLoginResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response([], 204);
        } else {
            return $this->traitSendLoginResponse($request);
        }
    }
    
    public function sendFailedLoginResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response(['errors' => ['username' => ['These credentials do not match our records.']]], 422);
        } else {
            return $this->traitSendFailedLoginResponse($request);
        }
    }

    protected function credentials(\Illuminate\Http\Request $request)
    {
        return array_merge($request->only($this->username(), 'password'), ['status' => 'ACTIVE']);
    }
}
