<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\UserSession;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;

class VerifyActiveSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookie = $request->cookie('laravel_token');

        if ($cookie) {
            $user = \Auth::user();
            $isActive = Cache::get('user_'.$user->id.'_active_session', true);
            if (!$isActive) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        return $next($request);
    }
}
