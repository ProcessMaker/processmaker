<?php

namespace ProcessMaker\Http\Middleware;

use Auth;
use Closure;
use Session;
use Carbon\Carbon;
use ProcessMaker\Events\SessionStarted as SessionStartedEvent;

class SessionStarted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            event(new SessionStartedEvent(Auth::user()));
        }
        
        return $next($request);
    }
}
