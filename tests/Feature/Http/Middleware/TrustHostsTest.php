<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Http\Middleware\TrustHosts;
use ProcessMaker\Multitenancy\SwitchTenant;
use ProcessMaker\Multitenancy\TenantDomainCache;
use Tests\TestCase;

class TrustHostsTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TrustHosts($this->app);
        SwitchTenant::$landlordValues = null;
        TenantDomainCache::forget();
    }

    protected function tearDown(): void
    {
        SwitchTenant::$landlordValues = null;
        TenantDomainCache::forget();
        parent::tearDown();
    }

    public function test_valid_trusted_host()
    {
        // Set app URL for testing
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://subdomain.example.processmaker.net');
        $request->headers->set('X-Forwarded-Host', 'subdomain.example.processmaker.net');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }

    public function test_valid_trusted_host_with_port()
    {
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://subdomain.example.processmaker.net');
        $request->headers->set('X-Forwarded-Host', 'subdomain.example.processmaker.net:443');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }

    public function test_valid_trusted_host_with_mixed_case()
    {
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://subdomain.example.processmaker.net');
        $request->headers->set('X-Forwarded-Host', 'SubDomain.Example.ProcessMaker.NET');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }

    public function test_valid_trusted_host_with_mixed_case_and_port()
    {
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://subdomain.example.processmaker.net');
        $request->headers->set('X-Forwarded-Host', 'SubDomain.Example.ProcessMaker.NET:8443');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }

    public function test_invalid_trusted_host()
    {
        // Set app URL for testing
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://malicious-site.com');
        $request->headers->set('X-Forwarded-Host', 'malicious-site.com');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Invalid Host Header');

        $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });
    }

    public function test_invalid_trusted_host_with_port_still_rejected()
    {
        Config::set('app.url', 'https://example.processmaker.net');

        $request = Request::create('https://malicious-site.com');
        $request->headers->set('X-Forwarded-Host', 'malicious-site.com:443');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Invalid Host Header');

        $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });
    }

    public function test_missing_forwarded_host()
    {
        $request = Request::create('https://example.processmaker.net');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }

    public function test_multitenancy_trusts_cached_tenant_domains_after_app_url_switch()
    {
        Config::set('app.multitenancy', true);
        Config::set('app.url', 'https://landlord.example.test');
        SwitchTenant::$landlordValues = [
            'app' => ['url' => 'https://landlord.example.test'],
        ];

        Cache::store(TenantDomainCache::STORE)->put(
            TenantDomainCache::CACHE_KEY,
            [
                'acme.example.test' => 1,
                'widgets.example.test' => 2,
            ],
            TenantDomainCache::TTL_SECONDS
        );

        // Simulate Octane after SwitchTenant narrowed config app.url to one tenant.
        Config::set('app.url', 'https://acme.example.test');

        $hosts = $this->middleware->hosts();

        $this->assertContains('^(.+\.)?' . preg_quote('landlord.example.test') . '$', $hosts);
        $this->assertContains('^(.+\.)?' . preg_quote('acme.example.test') . '$', $hosts);
        $this->assertContains('^(.+\.)?' . preg_quote('widgets.example.test') . '$', $hosts);

        $request = Request::create('https://widgets.example.test');
        $request->headers->set('X-Forwarded-Host', 'widgets.example.test');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }
}
