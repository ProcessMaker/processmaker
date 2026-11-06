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

class InstallPlugin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $maxExceptions = 1;

    public $timeout = 600;

    public function __construct(
        public int $userId,
        public ?string $url = null,
        public ?string $branch = null,
        public ?string $tag = null,
        public ?string $zipPath = null,
        public ?string $gitUsername = null,
        public ?string $gitToken = null,
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
        try {
            $label = $this->zipPath ? basename($this->zipPath) : ($this->url ?? 'plugin');
            event(new PluginLog('Starting queued plugin installation...', 'starting', $label, $this->userId));

            if ($this->zipPath) {
                $manager->installFromZip($this->zipPath, $this->userId);

                return;
            }

            $manager->install(
                $this->url,
                $this->branch,
                $this->tag,
                $this->userId,
                $this->gitUsername,
                $this->gitToken
            );
        } catch (Throwable $exception) {
            $manager->revertFailedInstall($this->userId);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $label = $this->zipPath ? basename((string) $this->zipPath) : ($this->url ?? 'plugin');
        event(new PluginLog(
            'Plugin installation failed: ' . $this->publicErrorMessage($exception),
            'error',
            $label,
            $this->userId
        ));
    }

    /**
     * Drop credentials that a process timeout can embed in its command line.
     */
    private function publicErrorMessage(Throwable $exception): string
    {
        $message = preg_replace('#://[^/\s:@]+:[^/\s@]+@#', '://', $exception->getMessage());

        return $message ?? $exception->getMessage();
    }
}
