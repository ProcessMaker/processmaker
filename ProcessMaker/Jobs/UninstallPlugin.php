<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Events\PluginLog;
use ProcessMaker\Managers\PluginManager;
use Throwable;

class UninstallPlugin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $maxExceptions = 1;

    public $timeout = 600;

    public function __construct(
        public string $pluginName,
        public int $userId,
    ) {
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('plugin-ops'))
                ->releaseAfter(30)
                ->expireAfter(630),
        ];
    }

    public function handle(PluginManager $manager): void
    {
        event(new PluginLog(
            "Starting queued plugin uninstallation: {$this->pluginName}",
            'starting',
            $this->pluginName,
            $this->userId
        ));

        $manager->uninstall($this->pluginName, $this->userId);
    }

    public function failed(Throwable $exception): void
    {
        event(new PluginLog(
            'Plugin uninstallation failed: ' . $exception->getMessage(),
            'error',
            $this->pluginName,
            $this->userId
        ));
    }
}
