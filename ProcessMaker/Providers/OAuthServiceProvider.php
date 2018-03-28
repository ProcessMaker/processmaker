<?php

namespace ProcessMaker\Providers;

use DateInterval;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider as ServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use ProcessMaker\OAuth2\AccessTokenRepository;
use ProcessMaker\OAuth2\AuthCodeRepository;
use ProcessMaker\OAuth2\ClientRepository;
use ProcessMaker\OAuth2\RefreshTokenRepository;
use ProcessMaker\OAuth2\ScopeRepository;
use ProcessMaker\OAuth2\UserRepository;

/**
 * Our Service Provider to register OAuth2 related services
 * @package ProcessMaker\Providers
 */
class OAuthServiceProvider extends ServiceProvider
{

    /**
     * @var bool We defer our services because we don't need to load the services all the time
     */
    protected $defer = true;

    /**
     * Register our required OAuth2 services
     */
    public function register()
    {
        $this->app->singleton(ResourceServer::class, function ($app) {
            $accessTokenRepository = new AccessTokenRepository();
            /**
             * @todo Determine if we need to cache this public key
             */
            $publicKey = new CryptKey(Storage::disk('keys')->get('public.key'));

            return new ResourceServer(
                $accessTokenRepository,
                $publicKey
            );
        });
        $this->app->singleton(AuthorizationServer::class, function ($app) {
            // Generate our OAuth2 Authorization server

            $clientRepository = new ClientRepository();
            $scopeRepository = new ScopeRepository();
            $accessTokenRepository = new AccessTokenRepository();
            $userRepository = new UserRepository();
            $refreshTokenRepository = new RefreshTokenRepository();
            $authCodeRepository = new AuthCodeRepository();

            $publicKey = new CryptKey(Storage::disk('keys')->get('public.key'));
            $privateKey = new CryptKey(Storage::disk('keys')->get('private.key'));

            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $privateKey,
                config('app.key')
            );

            // Allow the CLient Credentials Grant Type
            $grant = new ClientCredentialsGrant();
            $grant->setRefreshTokenTTL(new DateInterval('P1M'));
            $server->enableGrantType(
                $grant,
                new DateInterval('P1Y')
            );

            // Allow the Password Grant Type
            $grant = new PasswordGrant(
                $userRepository,
                $refreshTokenRepository
            );

            $grant->setRefreshTokenTTL(new DateInterval('P1M'));

            $server->enableGrantType(
                $grant,
                new DateInterval('P1Y')
            );

            // Allow the Authorization Code Grant Type
            $grant = new AuthCodeGrant(
                $authCodeRepository,
                $refreshTokenRepository,
                new DateInterval('P1Y')
            );

            $grant->setRefreshTokenTTL(new DateInterval('P1M'));

            $server->enableGrantType(
                $grant,
                new DateInterval('P1M')
            );

            $grant = new ImplicitGrant(new DateInterval('P1Y'));
            $server->enableGrantType(
                $grant,
                new DateInterval('P1M')
            );

            $grant = new RefreshTokenGrant(
                $refreshTokenRepository
            );
            $grant->setRefreshTokenTTL(new DateInterval('P1M'));
            $server->enableGrantType(
                $grant,
                new DateInterval('P1M')
            );

            return $server;
        });
    }

    /**
     * Provide the list of classes our provider can create
     * @return array
     */
    public function provides()
    {
        return [AuthorizationServer::class, ResourceServer::class];
    }
}
