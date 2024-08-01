<?php

namespace ProcessMaker\Http\Controllers\Auth;

use App;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\Logout;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\LoginManager;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Auth\Database\Seeds\AuthDefaultSeeder;
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

    protected $maxAttempts;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Set middle wares
        $this->middleware('session_block')->only('loginWithIntendedCheck');
        $this->middleware('guest')->except(['logout', 'beforeLogout', 'keepAlive']);
        $this->middleware('saml_request')->only('showLoginForm');

        // Set login attempts
        $loginAttempts = (int) config('password-policies.login_attempts', PHP_INT_MAX);
        if ($loginAttempts === 0) {
            $loginAttempts = PHP_INT_MAX;
        }
        $this->maxAttempts = $loginAttempts;
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
        // Review if we need to redirect the default SSO
        if (config('app.enable_default_sso')) {
            $arrayAddons = $addons->toArray();
            $driver = $this->getDefaultSSO($arrayAddons);
            // If a default SSO was defined we will to redirect
            if (!empty($driver)) {
                return redirect()->route('sso.redirect', ['driver' => $driver]);
            }
        }
        $block = $manager->getBlock();
        // clear cookie to avoid an issue when logout SLO and then try to login with simple PM login form
        \Cookie::queue(\Cookie::forget(config('session.cookie')));
        // cookie required here because SSO redirect resets the session
        $cookie = cookie(
            'processmaker_intended',
            redirect()->intended()->getTargetUrl(),
            10,
            null,
            null,
            true,
            true,
            false,
            'none'
        );
        $loginView = empty(config('app.login_view')) ? 'auth.login' : config('app.login_view');
        $response = response(view($loginView, compact('addons', 'block')));
        $response->withCookie($cookie);

        // Remove 'password_hash_web' from session
        $request = request();
        $request->session()->forget('password_hash_' . app('auth')->getDefaultDriver());

        return $response;
    }

    protected function getDefaultSSO(array $addons): string
    {
        $addonsData = !empty($addons) ? head($addons)->data : [];
        $defaultSSO = $this->getLoginDefaultSSO();
        $pmLogin = $this->getPmLogin();
        if (!empty($defaultSSO) && !empty($addonsData)) {
            // Get the config selected
            $position = $this->getColumnAttribute($defaultSSO, 'config', 'config');
            // Get the ui defined
            $elements = $this->getColumnAttribute($defaultSSO, 'ui', 'elements');
            $options = $this->getColumnAttribute($defaultSSO, 'ui', 'options');
            // Get the sso drivers configured
            $drivers = !empty($addonsData['drivers']) ? $addonsData['drivers'] : [];
            if (
                is_int($position)
                && $options[$position] !== $pmLogin
                && !empty($elements)
                && !empty($drivers)
            ) {
                // Get the specific element defined with the default SSO
                $element = !empty($elements[$position]->name) ? strtolower($elements[$position]->name) : '';
                if (!empty($element) && array_key_exists($element, $drivers)) {
                    return $element;
                }
            }
        }

        return '';
    }

    protected function getLoginDefaultSSO()
    {
        $defaultSSO = '';
        // Check if the package-auth is installed and has a const SSO_DEFAULT_LOGIN was defined
        if (class_exists(AuthDefaultSeeder::class)) {
            $defaultSSO = Setting::byKey('sso.default.login');
        }

        return $defaultSSO;
    }

    protected function getPmLogin()
    {
        return 'ProcessMaker';
    }

    protected function getColumnAttribute(object $setting, string $attribute, string $key = '')
    {
        $config = $setting->getAttribute($attribute);
        switch ($key) {
            case 'config':
                $result = !is_null($config) ? (int) $config : null;
                break;
            case 'elements':
                $result = !empty($config->elements) ? $config->elements : [];
                break;
            case 'options':
                $result = !empty($config->options) ? $config->options : [];
                break;
            default:
                $result = null;
        }

        return $result;
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
        } elseif ($user->status === 'BLOCKED') {
            $this->throwLockedLoginResponse();
        }

        $addons = $this->getPluginAddons('command', []);
        foreach ($addons as $addon) {
            if (array_key_exists('command', $addon)) {
                $command = $addon['command'];
                $command->execute($request, $request->input('username'));
            }
        }

        if (class_exists(\ProcessMaker\Package\Auth\Auth\LDAPLogin::class)) {
            $redirect = \ProcessMaker\Package\Auth\Auth\LDAPLogin::auth($user, $request->input('password'));
            if ($redirect !== false) {
                return $redirect;
            }
        }

        return $this->login($request, $user);
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
            //Clear the user permissions
            $request->session()->forget('permissions');

            //Clear the user permissions
            $userId = Auth::user()->id;
            $request->session()->forget("user_{$userId}_permissions");

            // Clear the user session
            $this->forgetUserSession();

            event(new Logout(Auth::user()));

            // Always destroy 2fa flag
            session()->remove(TwoFactorAuthController::TFA_VALIDATED);
            session()->remove(TwoFactorAuthController::TFA_MESSAGE);
            session()->remove(TwoFactorAuthController::TFA_ERROR);
        }

        return $this->logout($request);
    }

    private function forgetUserSession()
    {
        $userSession = session()->get('user_session');
        $user = Auth::user();
        $user->sessions()->where('token', $userSession)->update(['is_active' => false]);
        session()->forget('user_session');
    }

    public function loggedOut(Request $request)
    {
        $response = redirect(route('login'));
        if ($request->has('timeout')) {
            $response->with('timeout', true);
        }

        return $response;
    }

    /**
     * Handle a login request to the application.
     * Overrides the original login action.
     *
     * @param  Request $request
     * @param  User $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws ValidationException
     */
    public function login(Request $request, User $user)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            // Block the user
            $user->status = 'BLOCKED';
            $user->save();

            // Throw locked error message
            $this->throwLockedLoginResponse();
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            // Check if the user needs to change the password
            if ($request->filled(['SAMLRequest', 'RelayState']) && $user->force_change_password === 1) {
                // Store the SAMLRequest and RelayState in a cookie
                Cookie::queue(
                    'saml_request',
                    json_encode([
                        'SAMLRequest' => $request->get('SAMLRequest'),
                        'RelayState' => $request->get('RelayState'),
                    ]),
                    10,
                    null,
                    null,
                    true,
                    true,
                    false,
                    'none',
                );

                return redirect()->route('password.change');
            }
            // Cache user permissions for a day to improve performance
            Cache::remember("user_{$user->id}_permissions", 86400, function () use ($user) {
                return $user->permissions->pluck('name')->toArray();
            });

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Throws locked error message
     *
     * @throws ValidationException
     */
    protected function throwLockedLoginResponse()
    {
        throw ValidationException::withMessages([
            $this->username() => [_('Account locked after too many failed attempts. Contact administrator.')],
        ]);
    }

    public function showLoginFailed(Request $request)
    {
        return view('errors.login-failed');
    }
}
