<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Health;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Health\OctaneHeartbeat;
use Tests\TestCase;

class OctaneHeartbeatTest extends TestCase
{
    /** @var resource|null */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('octane.health.port', 18003);
        Config::set('octane.health.host', '127.0.0.1');
        Config::set('octane.health.endpoint', '/health/live');

        $this->listener = @stream_socket_server('tcp://127.0.0.1:18003');
    }

    protected function tearDown(): void
    {
        if (is_resource($this->listener)) {
            fclose($this->listener);
        }

        parent::tearDown();
    }

    public function test_it_fails_when_endpoint_returns_redirect(): void
    {
        Http::fake([
            '127.0.0.1:18003/*' => Http::response('', 302, ['Location' => '/login']),
        ]);

        $result = (new OctaneHeartbeat())->probe();

        $this->assertFalse($result->alive);
        $this->assertStringContainsString('HTTP 302', (string) $result->detail);
    }

    public function test_it_fails_when_endpoint_returns_server_error(): void
    {
        Http::fake([
            '127.0.0.1:18003/*' => Http::response('error', 500),
        ]);

        $result = (new OctaneHeartbeat())->probe();

        $this->assertFalse($result->alive);
        $this->assertStringContainsString('HTTP 500', (string) $result->detail);
    }
}
