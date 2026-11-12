<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Services\PluginRuntimeReload;
use Tests\TestCase;

class PluginReloadListenCommandTest extends TestCase
{
    private string $stateFile;

    private string $previousSocketTimeout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousSocketTimeout = (string) ini_get('default_socket_timeout');
        $this->stateFile = sys_get_temp_dir() . '/pm-plugin-reload-' . uniqid();
        config([
            'plugin_manager.supervisorctl_bin' => 'supervisorctl',
            'plugin_manager.supervisorctl_config' => null,
            'plugin_manager.supervisor_restart_target' => 'all',
            'plugin_manager.supervisor_restart_delay' => 5,
            'plugin_manager.reload_channel' => 'plugin-runtime-reload',
            'plugin_manager.reload_version_key' => 'plugin-runtime-reload:version',
            'plugin_manager.reload_state_file' => $this->stateFile,
            'plugin_manager.reload_reconnect_delay' => 0,
            'plugin_manager.reload_listen_max_cycles' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        ini_set('default_socket_timeout', $this->previousSocketTimeout);

        if (is_file($this->stateFile)) {
            unlink($this->stateFile);
        }

        parent::tearDown();
    }

    private function expectSubscriptionClient(): void
    {
        $client = \Mockery::mock();
        $client->shouldReceive('setOption')
            ->with(\Redis::OPT_READ_TIMEOUT, -1)
            ->andReturnTrue();

        $connection = \Mockery::mock();
        $connection->shouldReceive('client')->andReturn($client);

        Redis::shouldReceive('connection')->andReturn($connection);
    }

    public function testListenerCatchesUpWhenAReloadWasMissed(): void
    {
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);
        $this->expectSubscriptionClient();
        Redis::shouldReceive('get')
            ->once()
            ->with('plugin-runtime-reload:version')
            ->andReturn('4');
        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['test:plugin-runtime-reload'], \Mockery::type('Closure'));

        $this->artisan('plugin:reload-listen')->assertOk();

        $this->assertSame('4', file_get_contents($this->stateFile));
        Process::assertRan(function ($process) {
            $command = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;

            return str_contains($command, 'setsid')
                && str_contains($command, 'supervisorctl')
                && str_contains($command, 'restart')
                && str_contains($command, 'all');
        });
    }

    public function testListenerSchedulesRestartForANewMessageAndSkipsTheSameVersion(): void
    {
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);
        file_put_contents($this->stateFile, '2');

        $this->expectSubscriptionClient();
        Redis::shouldReceive('get')->once()->andReturn('2');
        Redis::shouldReceive('subscribe')
            ->once()
            ->andReturnUsing(function (array $channels, \Closure $callback): void {
                $this->assertSame(['test:plugin-runtime-reload'], $channels);
                $callback(json_encode(['version' => 3, 'plugin' => 'package-plg']), 'plugin-runtime-reload');
                $callback(json_encode(['version' => 3, 'plugin' => 'package-plg']), 'plugin-runtime-reload');
            });

        $this->artisan('plugin:reload-listen')->assertOk();

        $this->assertSame('3', file_get_contents($this->stateFile));
        Process::assertRanTimes(function ($process) {
            $command = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;

            return str_contains($command, 'supervisorctl');
        }, 1);
    }

    public function testPublishStoresTheVersionAndNotifiesTheChannel(): void
    {
        Redis::shouldReceive('incr')
            ->once()
            ->with('plugin-runtime-reload:version')
            ->andReturn(7);
        Redis::shouldReceive('publish')
            ->once()
            ->withArgs(function (string $channel, string $message): bool {
                $payload = json_decode($message, true);

                return $channel === 'test:plugin-runtime-reload'
                    && $payload['version'] === 7
                    && $payload['plugin'] === 'package-plg';
            })
            ->andReturn(1);

        $version = (new PluginRuntimeReload())->publish('package-plg');

        $this->assertSame(7, $version);
    }

    public function testChannelAndVersionKeyIncludeRedisAndTenantPrefixes(): void
    {
        config([
            'database.redis.options.prefix' => 'instance:',
            'plugin_manager.reload_channel' => 'plugin-runtime-reload',
            'plugin_manager.reload_version_key' => 'plugin-runtime-reload:version',
            'plugin_manager.reload_state_file' => '/tmp/processmaker-plugin-reload-version',
        ]);
        app()->instance('currentTenant', new class {
            public function getKey(): int
            {
                return 9;
            }
        });

        $reload = new PluginRuntimeReload();

        $this->assertSame('instance:tenant_9:plugin-runtime-reload', $reload->channel());
        $this->assertSame('tenant_9:plugin-runtime-reload:version', $reload->versionKey());
        $this->assertSame('/tmp/processmaker-plugin-reload-version-9', $reload->stateFile());
    }

    public function testListenerReconnectsAfterRedisReadError(): void
    {
        config(['plugin_manager.reload_listen_max_cycles' => 2]);
        Process::fake([
            '*' => Process::result('', exitCode: 0),
        ]);
        file_put_contents($this->stateFile, '4');

        $this->expectSubscriptionClient();
        Redis::shouldReceive('get')->twice()->andReturn('4', '5');
        Redis::shouldReceive('purge')->once()->with('default');
        Redis::shouldReceive('subscribe')
            ->twice()
            ->andReturnUsing(function (): void {
                static $attempt = 0;
                $attempt++;
                if ($attempt === 1) {
                    throw new \RedisException('read error on connection to 127.0.0.1:6379');
                }
            });

        $this->artisan('plugin:reload-listen')
            ->expectsOutputToContain('Reconnecting.')
            ->assertOk();

        $this->assertSame('5', file_get_contents($this->stateFile));
        Process::assertRanTimes(function ($process) {
            $command = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;

            return str_contains($command, 'supervisorctl');
        }, 1);
    }
}
