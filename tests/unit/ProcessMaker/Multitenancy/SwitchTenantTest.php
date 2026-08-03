<?php

namespace Tests\Unit\ProcessMaker\Multitenancy;

use Illuminate\Support\Facades\Context;
use ProcessMaker\Multitenancy\SwitchTenant;
use Tests\TestCase;

class SwitchTenantTest extends TestCase
{
    private const LANDLORD_VALUES_KEY = 'multitenancy.landlord_values';

    protected function tearDown(): void
    {
        Context::forget(self::LANDLORD_VALUES_KEY);

        parent::tearDown();
    }

    public function test_landlord_snapshot_persists_during_same_request(): void
    {
        config(['app.url' => 'https://landlord.example.com']);
        Context::add(self::LANDLORD_VALUES_KEY, config()->all());

        config(['app.url' => 'https://tenant-modified.example.com']);

        $this->assertSame(
            'https://landlord.example.com',
            $this->landlordConfig('app.url')
        );
    }

    public function test_landlord_values_are_not_reused_across_requests(): void
    {
        Context::add(self::LANDLORD_VALUES_KEY, ['app' => ['url' => 'https://tenant-a.example.com']]);
        Context::forget(self::LANDLORD_VALUES_KEY);

        config(['app.url' => 'https://tenant-b.example.com']);
        Context::add(self::LANDLORD_VALUES_KEY, config()->all());

        $this->assertSame(
            'https://tenant-b.example.com',
            Context::get(self::LANDLORD_VALUES_KEY)['app']['url']
        );
    }

    private function landlordConfig(string $key): mixed
    {
        $method = new \ReflectionMethod(SwitchTenant::class, 'landlordConfig');

        return $method->invoke(new SwitchTenant(), $key);
    }
}
