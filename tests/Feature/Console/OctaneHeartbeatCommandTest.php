<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OctaneHeartbeatCommandTest extends TestCase
{
    private const HEALTH_PORT = 18002;

    /** @var resource|null */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('octane.health.port', self::HEALTH_PORT);
        Config::set('octane.health.host', '127.0.0.1');
        Config::set('octane.health.endpoint', '/health/live');

        $this->listener = @stream_socket_server('tcp://127.0.0.1:' . self::HEALTH_PORT);
    }

    protected function tearDown(): void
    {
        if (is_resource($this->listener)) {
            fclose($this->listener);
        }

        parent::tearDown();
    }

    public function test_octane_heartbeat_reports_alive_when_listener_and_endpoint_respond(): void
    {
        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response('ok', 200),
        ]);

        $this->artisan('octane:heartbeat')
            ->expectsOutputToContain('Status: ALIVE')
            ->assertExitCode(0);
    }

    public function test_octane_heartbeat_reports_dead_when_listener_is_unavailable(): void
    {
        Config::set('octane.health.port', 59998);

        Http::fake();

        $this->artisan('octane:heartbeat')
            ->expectsOutputToContain('Status: DEAD')
            ->assertExitCode(1);
    }

    public function test_octane_heartbeat_php_ini_shows_worker_settings(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '3072M',
            'OCTANE_MAX_EXECUTION_TIME' => 90,
            'OCTANE_POST_MAX_SIZE' => '200M',
            'OCTANE_UPLOAD_MAX_FILESIZE' => '200M',
            'OCTANE_MAX_INPUT_VARS' => 9000,
            'OCTANE_MAX_INPUT_TIME' => 90,
        ]);

        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response([
                'status' => 'ok',
                'php_version' => '8.5.8',
                'php' => [
                    'memory_limit' => '3072M',
                    'max_execution_time' => '90',
                    'post_max_size' => '200M',
                    'upload_max_filesize' => '200M',
                    'max_input_vars' => '9000',
                    'max_input_time' => '90',
                ],
                'extensions' => [
                    'grpc' => true,
                    'redis' => true,
                    'rdkafka' => false,
                ],
                'caddyfile' => base_path('Caddyfile'),
            ], 200),
        ]);

        $this->artisan('octane:heartbeat --php-ini')
            ->expectsOutputToContain('PHP configuration')
            ->expectsOutputToContain('php.ini settings')
            ->expectsOutputToContain('CLI PHP (php.ini)')
            ->expectsOutputToContain('FrankenPHP worker')
            ->expectsOutputToContain('.env target')
            ->expectsOutputToContain('Memory limit')
            ->assertExitCode(0);
    }

    public function test_octane_heartbeat_php_ini_reports_mismatch(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '3072M',
            'OCTANE_MAX_EXECUTION_TIME' => 90,
            'OCTANE_POST_MAX_SIZE' => '200M',
            'OCTANE_UPLOAD_MAX_FILESIZE' => '200M',
            'OCTANE_MAX_INPUT_VARS' => 9000,
            'OCTANE_MAX_INPUT_TIME' => 90,
        ]);

        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response([
                'status' => 'ok',
                'php_version' => '8.5.8',
                'php' => [
                    'memory_limit' => '128M',
                    'max_execution_time' => '90',
                    'post_max_size' => '200M',
                    'upload_max_filesize' => '200M',
                    'max_input_vars' => '9000',
                    'max_input_time' => '90',
                ],
                'extensions' => [],
                'caddyfile' => base_path('Caddyfile'),
            ], 200),
        ]);

        $this->artisan('octane:heartbeat --php-ini')
            ->expectsOutputToContain('Status: ALIVE')
            ->expectsOutputToContain('Mismatch')
            ->expectsOutputToContain('setting(s) mismatch .env')
            ->assertExitCode(1);
    }

    public function test_octane_heartbeat_php_ini_reports_dead_when_worker_values_unavailable(): void
    {
        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response('ok', 200),
        ]);

        $this->artisan('octane:heartbeat --php-ini')
            ->expectsOutputToContain('Status: DEAD')
            ->expectsOutputToContain('Worker php.ini could not be read')
            ->doesntExpectOutputToContain('configured, not verified')
            ->assertExitCode(1);
    }

    public function test_octane_heartbeat_quiet_mode_prints_single_line(): void
    {
        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response('ok', 200),
        ]);

        $this->artisan('octane:heartbeat -q')
            ->expectsOutputToContain('octane:heartbeat: alive')
            ->assertExitCode(0);
    }

    public function test_octane_heartbeat_ready_shows_platform_checks(): void
    {
        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response('ok', 200),
        ]);

        $this->artisan('octane:heartbeat --ready')
            ->expectsOutputToContain('Platform checks')
            ->expectsOutputToContain('Database:')
            ->assertExitCode(0);
    }

    public function test_octane_heartbeat_liveness_does_not_show_platform_checks(): void
    {
        Http::fake([
            '127.0.0.1:' . self::HEALTH_PORT . '/*' => Http::response('ok', 200),
        ]);

        $this->artisan('octane:heartbeat')
            ->doesntExpectOutputToContain('Platform checks')
            ->assertExitCode(0);
    }
}
