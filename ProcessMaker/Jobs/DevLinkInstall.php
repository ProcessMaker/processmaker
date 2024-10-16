<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\DevLink;
use Throwable;

class DevLinkInstall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $maxExceptions = 1;

    public $devLink = null;

    public function __construct(
        public int $userId,
        public int $devLinkId,
        public int $bundleId,
        public string $importMode = 'update',
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->devLink = DevLink::findOrFail($this->devLinkId);
        $this->devLink->logger = new Logger($this->userId);

        $lock = Cache::lock(ImportV2::CACHE_LOCK_KEY, ImportV2::RELEASE_LOCK_AFTER);

        if ($lock->get()) {
            $this->devLink->logger->clear();
            $this->devLink->installRemoteBundle($this->bundleId);
            $lock->release();
        } else {
            // Don't throw exception because that will unlock the running job
            $this->devLink->logger->error('Already running!');
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
