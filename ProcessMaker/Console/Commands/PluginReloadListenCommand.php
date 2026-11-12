<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use ProcessMaker\Services\PluginRuntimeReload;
use Throwable;

class PluginReloadListenCommand extends Command
{
    protected $signature = 'plugin:reload-listen';

    protected $description = 'Listen for plugin changes and restart supervisor programs on this pod';

    public function handle(PluginRuntimeReload $reload): int
    {
        $cycle = 0;

        while ($this->shouldListen($cycle)) {
            $cycle++;

            try {
                $this->catchUp($reload);
                $this->disableReadTimeout();
                $this->info('Listening for plugin runtime reloads on [' . $reload->channel() . '].');
                $this->subscribe($reload);
                $this->warn('Plugin reload subscription ended. Reconnecting.');
            } catch (Throwable $exception) {
                $this->warn(
                    'Plugin reload listener lost Redis: ' . $exception->getMessage() . ' Reconnecting.'
                );
            }

            if (!$this->shouldListen($cycle)) {
                break;
            }

            $this->releaseRedis();
            $this->pauseBeforeReconnect();
        }

        return self::SUCCESS;
    }

    private function shouldListen(int $cycle): bool
    {
        $maxCycles = (int) config('plugin_manager.reload_listen_max_cycles', 0);

        return $maxCycles === 0 || $cycle < $maxCycles;
    }

    /**
     * PhpRedis treats a read timeout of 0 as PHP's default_socket_timeout.
     * A subscribe with no messages then dies with "read error on connection".
     */
    private function disableReadTimeout(): void
    {
        ini_set('default_socket_timeout', '-1');

        $client = Redis::connection()->client();
        if (is_object($client) && method_exists($client, 'setOption')) {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        }
    }

    private function catchUp(PluginRuntimeReload $reload): void
    {
        $remoteVersion = $reload->remoteVersion();
        if ($remoteVersion > $reload->appliedVersion()) {
            $this->applyReload($reload, $remoteVersion);
        }
    }

    private function subscribe(PluginRuntimeReload $reload): void
    {
        Redis::subscribe([$reload->channel()], function (string $message) use ($reload): void {
            $version = $reload->versionFromMessage($message);
            if ($version <= $reload->appliedVersion()) {
                return;
            }

            $this->applyReload($reload, $version);
        });
    }

    private function releaseRedis(): void
    {
        try {
            Redis::purge('default');
        } catch (Throwable) {
            // The broken connection is discarded on the next attempt.
        }
    }

    private function pauseBeforeReconnect(): void
    {
        $seconds = max(0, (int) config('plugin_manager.reload_reconnect_delay', 1));
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    private function applyReload(PluginRuntimeReload $reload, int $version): bool
    {
        if ($version > 0 && !$reload->rememberApplied($version)) {
            $this->error('Could not record the applied plugin reload version.');

            return false;
        }

        if (!$reload->scheduleSupervisorRestart()) {
            $this->error('Could not schedule the local supervisor restart.');

            return false;
        }

        $this->info('Scheduled supervisorctl restart on this pod.');

        return true;
    }
}
