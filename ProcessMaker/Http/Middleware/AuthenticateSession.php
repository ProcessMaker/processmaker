<?php

namespace ProcessMaker\Http\Middleware;

use BadMethodCallException;
use Closure;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;

class AuthenticateSession extends BaseAuthenticateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        if (!$request->hasSession() || !$request->user()) {
            return $next($request);
        }

        // On occasion, the "auth" property is an empty array
        // and trying to call the viaRemember() method off of
        // it will throw a fatal error, this is a work around
        try {
            if ($this->auth->viaRemember()) {
                $passwordHash = explode('|', $request->cookies->get($this->auth->getRecallerName()))[2] ?? null;

                if (!$passwordHash || $passwordHash != $request->user()->getAuthPassword()) {
                    $this->logout($request);
                }
            }
        } catch (BadMethodCallException $exception) {
            return $next($request);
        }

        if (!$request->session()->has('password_hash')) {
            $this->storePasswordHashInSession($request);
        }

        if ($request->session()->get('password_hash') !== $request->user()->getAuthPassword()) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            $this->storePasswordHashInSession($request);
        });
    }
}
