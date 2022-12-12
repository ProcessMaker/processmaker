<?php

namespace ProcessMaker\Http\Controllers\Auth;

use App;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use ProcessMaker\Events\Logout;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\LoginManager;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasControllerAddons;

class LoginController extends Controller
{
    use HasControllerAddons;
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

    use AuthenticatesUsers;

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
        $this->middleware('guest')->except(['logout', 'beforeLogout', 'keepAlive']);
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        $manager = App::make(LoginManager::class);
        $addons = $manager->list();
        $block = $manager->getBlock();
        // clear cookie to avoid an issue when logout SLO and then try to login with simple PM login form
        \Cookie::queue(\Cookie::forget(config('session.cookie')));
        // cookie required here because SSO redirect resets the session
        $cookie = cookie('processmaker_intended', redirect()->intended()->getTargetUrl(), 10, '/');
        $response = response(view('auth.login', compact('addons', 'block')));
        $response->withCookie($cookie);

        return $response;
    }

    public function loginWithIntendedCheck(Request $request)
    {
        $intended = Cookie::get('processmaker_intended');
        if ($intended) {
            // Check if the route is a fallback, meaning it's invalid (like favicon.ico)
            $route = app('router')->getRoutes()->match(
                app('request')->create($intended)
            );
            if ($route->isFallback) {
                $intended = false;
            }

            // Getting intended deletes it, so put in back
            $request->session()->put('url.intended', $intended);
        }

        // Check the status of the user
        $user = User::where('username', $request->input('username'))->first();
        if (!$user || $user->status === 'INACTIVE') {
            $this->sendFailedLoginResponse($request);
        }

        $addons = $this->getPluginAddons('command', []);
        foreach ($addons as $addon) {
            if (array_key_exists('command', $addon)) {
                $command = $addon['command'];
                $command->execute($request, $request->input('username'));
            }
        }

        return $this->login($request);
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

    public function keepAlive()
    {
        return response('', 204);
    }

    protected function authenticated(Request $request, $user)
    {
        if (env('LOGOUT_OTHER_DEVICES', false)) {
            Auth::logoutOtherDevices($request->input('password'));
        }
    }

    public function beforeLogout(Request $request)
    {
        if (Auth::check()) {
            event(new Logout(Auth::user()));
        }

        return $this->logout($request);
    }

    public function loggedOut(Request $request)
    {
        $response = redirect(route('login'));
        if ($request->has('timeout')) {
            $response->with('timeout', true);
        }

        return $response;
    }
}
