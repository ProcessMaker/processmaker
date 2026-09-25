<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Auth;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use ProcessMaker\Auth\PassportTokenGuardFactory;
use ReflectionClass;
use Tests\TestCase;

class PassportTokenGuardFactoryTest extends TestCase
{
    public function test_make_uses_the_encrypter_from_the_given_application(): void
    {
        $encrypter = new Encrypter(
            Encrypter::generateKey(config('app.cipher')),
            config('app.cipher')
        );

        $app = app();
        $app->instance('encrypter', $encrypter);
        $app->instance('request', Request::create('/'));
        $app->instance(ResourceServer::class, Mockery::mock(ResourceServer::class));
        $app->instance(ClientRepository::class, Mockery::mock(ClientRepository::class));

        $guard = (new PassportTokenGuardFactory())->make($app, [
            'provider' => 'users',
        ]);

        $this->assertInstanceOf(TokenGuard::class, $guard);
        $this->assertSame($encrypter, $this->guardEncrypter($guard));
    }

    public function test_passport_guard_uses_the_auth_manager_application_encrypter(): void
    {
        $root = app();
        $auth = $root->make('auth');

        $sandbox = clone $root;
        $sandboxEncrypter = new Encrypter(
            Encrypter::generateKey(config('app.cipher')),
            config('app.cipher')
        );
        $sandbox->instance('encrypter', $sandboxEncrypter);
        $sandbox->instance('request', Request::create('/'));
        $sandbox->instance(ResourceServer::class, Mockery::mock(ResourceServer::class));
        $sandbox->instance(ClientRepository::class, Mockery::mock(ClientRepository::class));

        $auth->setApplication($sandbox);
        $auth->forgetGuards();

        try {
            $guard = $auth->guard('api');

            $this->assertInstanceOf(TokenGuard::class, $guard);
            $this->assertSame($sandboxEncrypter, $this->guardEncrypter($guard));
        } finally {
            $auth->setApplication($root);
            $auth->forgetGuards();
        }
    }

    private function guardEncrypter(TokenGuard $guard): object
    {
        $property = (new ReflectionClass($guard))->getProperty('encrypter');

        return $property->getValue($guard);
    }
}
