<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Str;
use League\OAuth2\Server\Exception\OAuthServerException;
use ProcessMaker\Services\InvalidBearerTokenCache;

class ProcessMakerAuthenticate extends Authenticate
{
    /**
     * {@inheritdoc}
     *
     * After a successful authenticate(), reject BLOCKED/INACTIVE users so every auth:api route
     * (core and packages) is covered without listing middleware per route file.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        if ($blocked = EnsureAccountAllowsAccess::blockingResponseForRequest($request)) {
            return $blocked;
        }

        return $next($request);
    }

    protected function authenticate($request, array $guards)
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport($request, $guards);

        $this->addAcceptJsonHeaderIfApiCall($request, $guards);

        if (auth()->check() && !$request->ajax()) {
            $permissions = $request->user()->loadPermissions();
            session(['permissions' => $permissions]);
        }

        try {
            return parent::authenticate($request, $guards);
        } catch (OAuthServerException $e) {
            InvalidBearerTokenCache::rememberPassportJwtFailure($request, $guards);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function unauthenticated($request, array $guards)
    {
        InvalidBearerTokenCache::rememberPassportJwtFailure($request, $guards);

        throw new AuthenticationException('Unauthenticated.', $guards);
    }

    private function addAcceptJsonHeaderIfApiCall(\Illuminate\Http\Request $request, array $guards): void
    {
        if (in_array('api', $guards) && !$this->requestHasAcceptJsonHeader($request)) {
            $request->headers->set('accept', 'application/json,' . $request->header('accept'));
        }
    }

    private function requestHasAcceptJsonHeader(\Illuminate\Http\Request $request): bool
    {
        return Str::contains($request->header('accept'), ['/json', '+json']);
    }
}
