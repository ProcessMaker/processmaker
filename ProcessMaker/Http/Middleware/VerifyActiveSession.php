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
        if (!$request->hasHeader('Authorization')) {
            $user = \Auth::user();
            $activeSession = Cache::get('user_' . $user->id . '_active_session');
            $isActive = $activeSession ? $activeSession['active'] : true;
            if (!$isActive) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            else {
                $lastActivity = $activeSession['updated_at'];
                // refresh the cache key lifetime
                if (now()->diffInMinutes($lastActivity) > config('session.lifetime') / 2) {
                    Cache::put(
                        'user_' . $user->id . '_active_session',
                        ['active' => true, 'updated_at' => now()],
                        now()->addMinutes(config('session.lifetime'))
                    );
                }
            }
        }

        return $next($request);
    }
}
