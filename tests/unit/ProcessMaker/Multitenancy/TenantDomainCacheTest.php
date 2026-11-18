<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Multitenancy;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Multitenancy\Tenant;
use ProcessMaker\Multitenancy\TenantDomainCache;
use Tests\TestCase;

class TenantDomainCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.multitenancy' => true,
            // Reuse the test DB so landlord Tenant queries work in CI.
            'database.connections.landlord.database' => config('database.connections.processmaker.database'),
        ]);

        $this->ensureTenantsTable();
        TenantDomainCache::forget();
        Tenant::query()->delete();
    }

    protected function tearDown(): void
    {
        TenantDomainCache::forget();
        parent::tearDown();
    }

    public function test_map_returns_empty_when_multitenancy_disabled(): void
    {
        config(['app.multitenancy' => false]);

        $this->assertSame([], TenantDomainCache::map());
    }

    public function test_map_caches_domain_to_id_and_forget_clears_it(): void
    {
        $first = Tenant::create([
            'domain' => 'acme.example.test',
            'name' => 'Acme',
            'database' => 'tenant_acme_' . uniqid(),
            'config' => ['app.url' => 'https://acme.example.test'],
        ]);
        $second = Tenant::create([
            'domain' => 'widgets.example.test',
            'name' => 'Widgets',
            'database' => 'tenant_widgets_' . uniqid(),
            'config' => ['app.url' => 'https://widgets.example.test'],
        ]);

        // Creates clear the cache via model events; populate once.
        $map = TenantDomainCache::map();

        $this->assertSame([
            'acme.example.test' => $first->id,
            'widgets.example.test' => $second->id,
        ], $map);
        $this->assertTrue(
            Cache::store(TenantDomainCache::STORE)->has(TenantDomainCache::CACHE_KEY)
        );

        TenantDomainCache::forget();

        $this->assertFalse(
            Cache::store(TenantDomainCache::STORE)->has(TenantDomainCache::CACHE_KEY)
        );
    }

    public function test_saving_a_tenant_forgets_the_cache(): void
    {
        Tenant::create([
            'domain' => 'before.example.test',
            'name' => 'Before',
            'database' => 'tenant_before_' . uniqid(),
            'config' => ['app.url' => 'https://before.example.test'],
        ]);

        TenantDomainCache::map();
        $this->assertTrue(
            Cache::store(TenantDomainCache::STORE)->has(TenantDomainCache::CACHE_KEY)
        );

        Tenant::create([
            'domain' => 'after.example.test',
            'name' => 'After',
            'database' => 'tenant_after_' . uniqid(),
            'config' => ['app.url' => 'https://after.example.test'],
        ]);

        $this->assertFalse(
            Cache::store(TenantDomainCache::STORE)->has(TenantDomainCache::CACHE_KEY)
        );
    }

    private function ensureTenantsTable(): void
    {
        if (Schema::connection('landlord')->hasTable('tenants')) {
            return;
        }

        Schema::connection('landlord')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('database')->unique();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }
}
