<?php

namespace Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Octane\ApplicationGateway;
use ProcessMaker\Http\Middleware\ServerTimingMiddleware;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\ProcessMakerServiceProvider;
use ReflectionClass;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ServerTimingMiddlewareTest extends TestCase
{
    use RequestHelper;

    protected function tearDown(): void
    {
        ProcessMakerServiceProvider::beginRequestTiming();

        parent::tearDown();
    }

    private function getHeader($response, $header)
    {
        $headers = $response->headers->all();

        return $headers[$header];
    }

    private function getMetricDuration($response, string $metric): float
    {
        $serverTiming = implode(',', $this->getHeader($response, 'server-timing'));

        preg_match('/(?:^|,)\\s*' . preg_quote($metric, '/') . ';dur=([\\d.]+)/', $serverTiming, $matches);

        $this->assertArrayHasKey(1, $matches, "The {$metric} metric was not present in the Server-Timing header.");

        return (float) $matches[1];
    }

    private function recordQueryDuration(float $milliseconds): void
    {
        $connection = DB::connection();

        event(new QueryExecuted('SELECT 1', [], $milliseconds, $connection));
    }

    public function testServerTimingHeaderIncludesAllMetrics()
    {
        Route::middleware(ServerTimingMiddleware::class)->get('/test', function () {
            // Simulate a query
            DB::select('SELECT SLEEP(1)');

            return response()->json(['message' => 'Test endpoint']);
        });

        // Send a GET request
        $response = $this->get('/test');
        $response->assertStatus(200);
        // Assert the response has the Server-Timing header
        $response->assertHeader('Server-Timing');

        $serverTiming = $this->getHeader($response, 'server-timing');
        $this->assertStringContainsString('provider;dur=', $serverTiming[0]);
        $this->assertStringContainsString('controller;dur=', $serverTiming[1]);
        $this->assertStringContainsString('db;dur=', $serverTiming[2]);
    }

    public function testBeginRequestTimingClearsAccumulatedQueryTime()
    {
        $reflection = new ReflectionClass(ProcessMakerServiceProvider::class);
        $property = $reflection->getProperty('queryTime');
        $property->setAccessible(true);
        $property->setValue(null, 500);

        Route::middleware(ServerTimingMiddleware::class)->get('/timing-reset-test', function () {
            DB::select('SELECT 1');

            return response()->json(['message' => 'Timing reset test']);
        });

        $response = $this->get('/timing-reset-test');
        $serverTiming = $this->getHeader($response, 'server-timing');

        preg_match('/db;dur=([\d.]+)/', implode(',', $serverTiming), $matches);
        $dbTime = (float) ($matches[1] ?? 500);

        $this->assertLessThan(500, $dbTime);
    }

    public function testQueryTimeIsMeasured()
    {
        // Mock a route with a query
        Route::middleware(ServerTimingMiddleware::class)->get('/query-test', function () {
            DB::select('SELECT SLEEP(0.2)');

            return response()->json(['message' => 'Query test']);
        });

        // Send a GET request
        $response = $this->get('/query-test');
        // Extract the Server-Timing header
        $serverTiming = $this->getHeader($response, 'server-timing');
        // Assert the db timing is greater than 200ms (SLEEP simulates query time)
        preg_match('/db;dur=([\d.]+)/', $serverTiming[2], $matches);
        $dbTime = $matches[1] ?? 0;

        $this->assertGreaterThanOrEqual(200, (float) $dbTime);
    }

    public function testOctaneGatewayIsolatesQueryTimingAcrossConsecutiveRequests()
    {
        Route::middleware(ServerTimingMiddleware::class)->get('/octane-query/slow', function () {
            $this->recordQueryDuration(5000);

            return response()->json(['request' => 'slow']);
        });

        Route::middleware(ServerTimingMiddleware::class)->get('/octane-query/fast', function () {
            $this->recordQueryDuration(10);

            return response()->json(['request' => 'fast']);
        });

        $gateway = new ApplicationGateway($this->app, $this->app);

        $firstRequest = Request::create('/octane-query/slow');
        $firstResponse = $gateway->handle($firstRequest);
        $firstRequestQueryTime = $this->getMetricDuration($firstResponse, 'db');

        $this->assertGreaterThanOrEqual(5000, $firstRequestQueryTime);

        $gateway->terminate($firstRequest, $firstResponse);

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());

        $secondRequest = Request::create('/octane-query/fast');
        $secondResponse = $gateway->handle($secondRequest);
        $secondRequestQueryTime = $this->getMetricDuration($secondResponse, 'db');

        $this->assertGreaterThanOrEqual(10, $secondRequestQueryTime);
        $this->assertLessThan(5000, $secondRequestQueryTime);
        $this->assertLessThan($firstRequestQueryTime, $secondRequestQueryTime);

        $gateway->terminate($secondRequest, $secondResponse);

        $this->assertSame(0.0, ProcessMakerServiceProvider::getQueryTime());
    }

    public function testServiceProviderTimeIsMeasured()
    {
        // Mock a route
        Route::middleware(ServerTimingMiddleware::class)->get('/providers-test', function () {
            return response()->json(['message' => 'Providers test']);
        });

        // Send a GET request
        $response = $this->get('/providers-test');
        // Extract the Server-Timing header
        $serverTiming = $this->getHeader($response, 'server-timing');

        // Assert the providers timing is present and greater than or equal to 0
        preg_match('/provider;dur=([\d.]+)/', $serverTiming[0], $matches);
        $providersTime = $matches[1] ?? null;

        $this->assertNotNull($providersTime);
        $this->assertGreaterThanOrEqual(0, (float) $providersTime);
    }

    public function testControllerTimingIsMeasuredCorrectly()
    {
        // Mock a route
        Route::middleware(ServerTimingMiddleware::class)->get('/controller-test', function () {
            usleep(300000); // Simulate 300ms delay in the controller

            return response()->json(['message' => 'Controller timing test']);
        });

        // Send a GET request
        $response = $this->get('/controller-test');
        // Extract the Server-Timing header
        $serverTiming = $this->getHeader($response, 'server-timing');

        // Assert the controller timing is greater than 300ms
        preg_match('/controller;dur=([\d.]+)/', $serverTiming[1], $matches);
        $controllerTime = $matches[1] ?? 0;

        $this->assertGreaterThanOrEqual(300, (float) $controllerTime);
    }

    public function testProvidersTimingIsMeasuredCorrectly()
    {
        // Mock a route
        Route::middleware(ServerTimingMiddleware::class)->get('/providers-test', function () {
            return response()->json(['message' => 'Providers timing test']);
        });

        // Send a GET request
        $response = $this->get('/providers-test');
        // Extract the Server-Timing header
        $serverTiming = $this->getHeader($response, 'server-timing');

        // Assert the providers timing is present and greater than or equal to 0
        preg_match('/provider;dur=([\d.]+)/', $serverTiming[0], $matches);
        $providersTime = $matches[1] ?? null;

        $this->assertNotNull($providersTime);
        $this->assertGreaterThanOrEqual(0, (float) $providersTime);
    }

    public function testServerTimingOnLogin()
    {
        $user = User::factory()->create([
            'username' =>'john',
        ]);
        $this->actingAs($user, 'web');

        $response = $this->get('/login');
        $response->assertHeader('Server-Timing');

        $serverTiming = $this->getHeader($response, 'server-timing');
        $this->assertStringContainsString('provider;dur=', $serverTiming[0]);
        $this->assertStringContainsString('controller;dur=', $serverTiming[1]);
        $this->assertStringContainsString('db;dur=', $serverTiming[2]);
    }

    public function testServerTimingIfIsDisabled()
    {
        config(['app.server_timing.enabled' => false]);

        Route::middleware(ServerTimingMiddleware::class)->get('/test', function () {
            // Simulate a query
            DB::select('SELECT SLEEP(1)');

            return response()->json(['message' => 'Test endpoint']);
        });

        // Send a GET request
        $response = $this->get('/test');
        $response->assertStatus(200);
        // Assert the response has not the Server-Timing header
        $response->assertHeaderMissing('Server-Timing');
    }
}
