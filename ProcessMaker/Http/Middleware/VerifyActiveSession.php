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
            $cacheKey = 'user_' . $user->id . '_active_session_' . $request->cookie('device_id');

            $activeSession = Cache::get($cacheKey);
            $isActive = $activeSession ? $activeSession['active'] : false;
            if (!$isActive) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            else {
                $lastActivity = $activeSession ? $activeSession['updated_at'] : now();
                // refresh the cache entry's lifetime
                if (now()->diffInMinutes($lastActivity) > config('session.lifetime') / 2) {
                    Cache::put(
                        $cacheKey,
                        ['active' => true, 'updated_at' => now()],
                        now()->addMinutes(config('session.lifetime'))
                    );
                }
            }
        }

        return $next($request);
    }
}
