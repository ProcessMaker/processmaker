<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Multitenancy;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
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

    public function test_make_current_does_not_require_missing_tenant_route_cache(): void
    {
        $app = app();
        // Simulate landlord route cache already evaluated during boot.
        $app->instance('routes.cached', true);

        $tenant = $this->fakeTenant();
        $tenantRouteCache = base_path('storage/tenant_' . $tenant->id . '/routes-v7.php');
        $this->assertFileDoesNotExist($tenantRouteCache);

        $switch = new SwitchTenant();

        try {
            $switch->makeCurrent($tenant);

            $this->assertSame($tenantRouteCache, $app->getCachedRoutesPath());
            $this->assertFalse($app->routesAreCached());
        } finally {
            $switch->forgetCurrent();
            Env::getRepository()->clear('APP_ROUTES_CACHE');
            $app->forgetInstance('routes.cached');
        }
    }

    public function test_make_current_invalidates_routes_cached_when_not_running_in_console(): void
    {
        $app = app();
        $app->instance('routes.cached', true);

        // Mirror APP_RUNNING_IN_CONSOLE=false: shouldReinitializeRouter() is false,
        // but the stale landlord routes.cached binding must still be cleared.
        $property = (new ReflectionObject($app))->getProperty('isRunningInConsole');
        $previousRunningInConsole = $property->getValue($app);
        $property->setValue($app, false);

        $tenant = $this->fakeTenant();
        $this->assertFileDoesNotExist(base_path('storage/tenant_' . $tenant->id . '/routes-v7.php'));

        $switch = new SwitchTenant();

        try {
            $switch->makeCurrent($tenant);

            $this->assertFalse($app->routesAreCached());
        } finally {
            $switch->forgetCurrent();
            Env::getRepository()->clear('APP_ROUTES_CACHE');
            $app->forgetInstance('routes.cached');
            $property->setValue($app, $previousRunningInConsole);
        }
    }

    public function test_make_current_flushes_cached_filesystem_disks_to_tenant_root(): void
    {
        // Warm the landlord disk the way a Horizon worker might before the first tenant job.
        $landlordPath = Storage::disk('local')->path('import/options.json');
        $this->assertStringContainsString('/storage/app/', $landlordPath);

        $tenant = $this->fakeTenant();
        $switch = new SwitchTenant();

        try {
            $switch->makeCurrent($tenant);

            $tenantPath = Storage::disk('local')->path('import/options.json');
            $this->assertSame(
                storage_path('app/import/options.json'),
                $tenantPath
            );
            $this->assertStringContainsString('/storage/tenant_' . $tenant->id . '/', $tenantPath);
            $this->assertStringNotContainsString('/storage/app/', $tenantPath);
        } finally {
            $switch->forgetCurrent();
        }

        $restoredPath = Storage::disk('local')->path('import/options.json');
        $this->assertSame($landlordPath, $restoredPath);
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
