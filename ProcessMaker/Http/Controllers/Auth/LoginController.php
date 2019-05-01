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
        \Log::info("INTENDED FROM POST ----- $intended");
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
    
}
