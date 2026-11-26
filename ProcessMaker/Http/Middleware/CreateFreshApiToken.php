<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken as PassportCreateFreshApiToken;
use Laravel\Passport\Passport;
use ProcessMaker\Models\AnonymousUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passport's CreateFreshApiToken, but never issues the API token cookie to the
 * anonymous system user (e.g. on anonymous Web Entry pages, where the `anon`
 * guard becomes the default guard). Any stale token cookie is expired.
 */
class CreateFreshApiToken extends PassportCreateFreshApiToken
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $response = parent::handle($request, $next, $guard);

        if ($request->cookies->has(Passport::cookie()) && $this->isAnonymous($request)) {
            $response->headers->setCookie(Cookie::forget(Passport::cookie()));
        }

        return $response;
    }

    protected function requestShouldReceiveFreshToken(Request $request): bool
    {
        return parent::requestShouldReceiveFreshToken($request) && !$this->isAnonymous($request);
    }

    private function isAnonymous(Request $request): bool
    {
        return AnonymousUser::isAnonymousIdentity($request->user($this->guard));
    }
}
