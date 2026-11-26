<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Str;
use ProcessMaker\Models\AnonymousUser;

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

        if ($this->isAnonymousUserOnApiRoute($request, $guards)) {
            $this->unauthenticated($request, $guards);
        }

        if ($blocked = EnsureAccountAllowsAccess::blockingResponseForRequest($request, $guards)) {
            return $blocked;
        }

        return $next($request);
    }

    /**
     * The anonymous system user may only reach API routes that explicitly allow
     * the `anon` guard (e.g. `auth:api,anon` Web Entry endpoints). Any other
     * `auth:api` route must reject it, however its token was obtained.
     */
    private function isAnonymousUserOnApiRoute($request, array $guards): bool
    {
        return in_array('api', $guards, true)
            && !in_array('anon', $guards, true)
            && AnonymousUser::isAnonymousIdentity($request->user());
    }

    protected function authenticate($request, array $guards)
    {
        $this->addAcceptJsonHeaderIfApiCall($request, $guards);

        // Load permissions into the session
        if (auth()->check() && !$request->ajax()) {
            $permissions = $request->user()->loadPermissions();
            session(['permissions' => $permissions]);
        }

        return parent::authenticate($request, $guards);
    }

    /**
     * @param array $guards
     * @param \Illuminate\Http\Request $request
     */
    private function addAcceptJsonHeaderIfApiCall(\Illuminate\Http\Request $request, array $guards): void
    {
        if (in_array('api', $guards) && !$this->requestHasAcceptJsonHeader($request)) {
            $request->headers->set('accept', 'application/json,' . $request->header('accept'));
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function requestHasAcceptJsonHeader(\Illuminate\Http\Request $request): bool
    {
        return Str::contains($request->header('accept'), ['/json', '+json']);
    }
}
