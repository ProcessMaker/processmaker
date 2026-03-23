<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAllowsAccess
{
    /**
     * Status values that cannot use the application while authenticated (aligned with LoginController).
     */
    private const DENIED_STATUSES = ['BLOCKED', 'INACTIVE'];

    /**
     * If any active guard has a blocked/inactive user, log out and return the appropriate response.
     * Used by the web middleware group and by ProcessMakerAuthenticate (covers all auth:api routes, including packages).
     */
    public static function blockingResponseForRequest(Request $request): ?Response
    {
        foreach (['web', 'api'] as $guard) {
            if (!Auth::guard($guard)->check()) {
                continue;
            }

            /** @var User $user */
            $user = Auth::guard($guard)->user();
            if (!$user instanceof User || !in_array($user->status, self::DENIED_STATUSES, true)) {
                continue;
            }

            return self::denyAccess($request, $guard, $user);
        }

        return null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $blocked = self::blockingResponseForRequest($request);
        if ($blocked !== null) {
            return $blocked;
        }

        return $next($request);
    }

    public static function denyAccess(Request $request, string $guard, User $user): Response
    {
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($guard === 'api' || $request->expectsJson()) {
            $message = $user->status === 'BLOCKED'
                ? __('Account locked after too many failed attempts. Contact administrator.')
                : __('Unauthorized');

            return response()->json(['message' => $message], 401);
        }

        return redirect()
            ->guest(route('login'))
            ->withErrors([
                'username' => $user->status === 'BLOCKED'
                    ? __('Account locked after too many failed attempts. Contact administrator.')
                    : __('These credentials do not match our records.'),
            ]);
    }
}
