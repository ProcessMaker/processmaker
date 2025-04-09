<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;
use ProcessMaker\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TrustHostsTest extends TestCase
{
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TrustHosts($this->app);
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

    public function test_missing_forwarded_host()
    {
        $request = Request::create('https://example.processmaker.net');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['status' => 'success']);
        });

        $this->assertEquals(200, $response->status());
    }
} 