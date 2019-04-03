<?php
namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Sets the locale based on url parameter
 * @package ProcessMaker\Http\Middleware
 */
class SetLocale
{

    /**
     * Handle request. If the request has a route parameter called lang, set the locale on our application
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the locale
        $locale = config('locale');
        $user = \Auth::user();
        if ($user && $user->language !== null) {
            $locale = \Auth::user()->language;
        }
        if ($locale) {
            // Use the App facade to set the locale for our request lifecycle
            App::setLocale($locale);
        }
        // Process next
        return $next($request);
    }
}
