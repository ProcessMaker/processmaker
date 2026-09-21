<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Multitenancy;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use ProcessMaker\Multitenancy\SwitchTenant;
use ProcessMaker\Multitenancy\Tenant;
use ReflectionObject;
use Tests\TestCase;

class SwitchTenantTest extends TestCase
{
    public function test_make_current_keeps_broadcast_manager_and_channel_callbacks(): void
    {
        $app = app();
        $manager = $app->make(BroadcastManager::class);
        $manager->connection()->channel('ProcessMaker.Models.User.{id}', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });

        $switch = new SwitchTenant();

        try {
            $switch->makeCurrent($this->fakeTenant());

            $this->assertSame($manager, $app->make(BroadcastManager::class));
            $this->assertTrue(
                $app->make(BroadcastManager::class)->connection()->getChannels()->has('ProcessMaker.Models.User.{id}')
            );
        } finally {
            $switch->forgetCurrent();
        }
    }

    public function test_make_current_flushes_passport_singletons_and_auth_guards(): void
    {
        $app = app();
        $app->instance(ResourceServer::class, Mockery::mock(ResourceServer::class));
        $app->instance(AuthorizationServer::class, Mockery::mock(AuthorizationServer::class));
        $app->instance(ClientRepository::class, Mockery::mock(ClientRepository::class));

        $auth = $app->make('auth');
        $auth->guard('web');

        $previousEncrypter = $app->make('encrypter');
        $tenant = $this->fakeTenant();

        $switch = new SwitchTenant();

        try {
            $switch->makeCurrent($tenant);

            $this->assertArrayNotHasKey(ResourceServer::class, $this->containerInstances($app));
            $this->assertArrayNotHasKey(AuthorizationServer::class, $this->containerInstances($app));
            $this->assertArrayNotHasKey(ClientRepository::class, $this->containerInstances($app));
            $this->assertSame([], $this->authGuards($auth));
            $this->assertNotSame($previousEncrypter, $app->make('encrypter'));
        } finally {
            $switch->forgetCurrent();
        }
    }

    private function fakeTenant(): Tenant
    {
        $tenantKey = 'base64:' . base64_encode(Encrypter::generateKey(config('app.cipher')));

        $tenant = new Tenant();
        $tenant->id = 999001;
        $tenant->domain = 'tenant-999001.test';
        $tenant->database = config('database.connections.processmaker.database');
        $tenant->config = [
            'app.url' => config('app.url'),
            'app.key' => Crypt::encryptString($tenantKey),
        ];

        return $tenant;
    }

    private function containerInstances($app): array
    {
        $property = (new ReflectionObject($app))->getProperty('instances');

        return $property->getValue($app);
    }

    private function authGuards($auth): array
    {
        $property = (new ReflectionObject($auth))->getProperty('guards');

        return $property->getValue($auth);
    }
}
