<?php

declare(strict_types=1);

namespace ProcessMaker\Auth;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\ResourceServer;

/**
 * Build Passport's TokenGuard from the given application container.
 *
 * Laravel Passport's default guard factory closes over the service provider's
 * root application. Under Octane that is the worker app, not the per-request
 * sandbox — so the guard keeps the landlord Encrypter after SwitchTenant
 * swaps APP_KEY. Resolving from the current app fixes cookie API 401s.
 */
class PassportTokenGuardFactory
{
    public function make(Application $app, array $config): TokenGuard
    {
        return tap(new TokenGuard(
            $app->make(ResourceServer::class),
            new PassportUserProvider(Auth::createUserProvider($config['provider']), $config['provider']),
            $app->make(ClientRepository::class),
            $app->make('encrypter'),
            $app->make('request')
        ), function (TokenGuard $guard) use ($app): void {
            $app->refresh('request', $guard, 'setRequest');
        });
    }
}
