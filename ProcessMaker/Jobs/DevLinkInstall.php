<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use Throwable;

class DevLinkInstall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $maxExceptions = 1;

    public function __construct(
        public int $userId,
        public int $devLinkId,
        public int $bundleId,
        public string $importMode,
        public bool $reinstall = false,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $devLink = DevLink::findOrFail($this->devLinkId);
        $logger = new Logger($this->userId);

        $lock = Cache::lock(ImportV2::CACHE_LOCK_KEY, ImportV2::RELEASE_LOCK_AFTER);

        if ($lock->get()) {
            DB::transaction(function () use ($devLink, $logger) {
                if ($this->reinstall) {
                    $bundle = Bundle::findOrFail($this->bundleId);
                    $bundle->reinstall($this->importMode, $logger);
                } else {
                    $devLink->logger = $logger;
                    $devLink->installRemoteBundle($this->bundleId, $this->importMode);
                }
            });
            $lock->release();
        } else {
            // Don't throw exception because that will unlock the running job
            $logger->error('Already running!');
        }
    }

    public function failed(Throwable $exception): void
    {
        (new Logger($this->userId))->exception($exception);

        // Unlock the job
        // We can't use $this->lock->release() here because this is run in a new instance
        Cache::lock(ImportV2::CACHE_LOCK_KEY)->forceRelease();
    }
}
