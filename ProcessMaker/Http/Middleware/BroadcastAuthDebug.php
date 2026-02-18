<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BroadcastAuthDebug
{
    /**
     * Log broadcast auth requests that fail (403, 401, 500) for debugging intermittent issues.
     * Enable with BROADCAST_AUTH_DEBUG=true in .env
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!env('BROADCAST_AUTH_DEBUG', false)) {
            return $response;
        }

        $user = Auth::user();

        if ($response->getStatusCode() >= 400) {
            Log::error('Broadcast auth failed', [
                'status' => $response->getStatusCode(),
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'has_session' => $request->hasSession(),
                'session_id' => $request->session()?->getId(),
                'channel' => $request->input('channel_name'),
                'cookie_present' => $request->hasCookie(config('session.cookie')),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return $response;
    }
}
