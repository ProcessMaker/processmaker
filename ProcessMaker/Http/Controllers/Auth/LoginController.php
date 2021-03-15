<?php
namespace ProcessMaker\Http\Controllers\Auth;

use App;
use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Managers\LoginManager;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use ProcessMaker\Traits\HasControllerAddons;
use Illuminate\Support\Facades\Auth;

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

    protected $redirectTo = '/requests';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'keepAlive']);
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
        return view('auth.login', compact('addons', 'block'));
    }

    public function loginWithIntendedCheck(Request $request) {
        $intended = redirect()->intended()->getTargetUrl();
        if ($intended) {
            // Check if the route is a fallback, meaning it's invalid (like favicon.ico)
            $route = app('router')->getRoutes() ->match(
                app('request') ->create($intended)
            );
            if ($route->isFallback) {
                $intended = false;
            }

            // Getting intended deletes it, so put in back
            $request->session()->put('url.intended', $intended);
        }
        
        // Check the status of the user
        $user = User::where('username', $request->input('username'))->firstOrFail();
        if ($user->status === 'INACTIVE') {
            return redirect()->back();
        }

        $addons = $this->getPluginAddons('command', []);
        foreach($addons as $addon) {
            if(array_key_exists('command', $addon)) {
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

    public function loggedOut(Request $request)
    {
        $response = redirect(route('login'));
        if ($request->has('timeout')) {
            $response->with('timeout', true);
        }
        return $response;
    }
}
