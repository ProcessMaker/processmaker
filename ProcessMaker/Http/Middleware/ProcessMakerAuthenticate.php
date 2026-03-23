<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Str;

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
