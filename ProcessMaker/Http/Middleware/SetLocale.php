<?php

namespace ProcessMaker\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * Sets the locale based on url parameter
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
        $locale = config('app.locale', 'en');

        if (($user = Auth::user()) && !empty($user->language)) {
            $locale = $user->language;
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
