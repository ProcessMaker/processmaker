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

        if ($response->getStatusCode() < 400) {
            return $response;
        }

        $user = Auth::user();
        $channelName = $request->input('channel_name');
        $channelInfo = $this->parseChannelInfo($channelName);

        Log::error('Broadcast auth failed', [
            'status' => $response->getStatusCode(),
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'user_is_anonymous' => $user && method_exists($user, 'isAnonymous') ? $user->isAnonymous : null,
            'has_session' => $request->hasSession(),
            'session_id' => $request->session()?->getId(),
            'channel_name' => $channelName,
            'channel_type' => $channelInfo['type'],
            'channel_resource_id' => $channelInfo['id'],
            'user_channel_mismatch' => $channelInfo['type'] === 'User' && $user && $channelInfo['id']
                ? (string) $user->id !== (string) $channelInfo['id']
                : null,
            'cookie_present' => $request->hasCookie(config('session.cookie')),
            'ip' => $request->ip(),
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'user_agent' => $request->userAgent(),
            'socket_id' => $request->input('socket_id'),
            'response_body' => $this->getResponseBody($response),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $response;
    }

    private function parseChannelInfo(?string $channelName): array
    {
        if (!$channelName) {
            return ['type' => null, 'id' => null];
        }
        // Strip tenant prefix: tenant_X.ProcessMaker.Models.User.14 -> ProcessMaker.Models.User.14
        $channel = preg_replace('/^tenant_\d+\./', '', $channelName);
        if (preg_match('/ProcessMaker\.Models\.User\.(\d+)/', $channel, $m)) {
            return ['type' => 'User', 'id' => $m[1]];
        }
        if (preg_match('/ProcessMaker\.Models\.ProcessRequest\.(\d+)/', $channel, $m)) {
            return ['type' => 'ProcessRequest', 'id' => $m[1]];
        }
        if (preg_match('/ProcessMaker\.Models\.ProcessRequestToken\.(\d+)/', $channel, $m)) {
            return ['type' => 'ProcessRequestToken', 'id' => $m[1]];
        }

        return ['type' => 'other', 'id' => null];
    }

    private function getResponseBody($response): ?string
    {
        $content = $response->getContent();
        if (is_string($content) && strlen($content) < 500) {
            return $content;
        }

        return $content ? '[truncated]' : null;
    }
}
