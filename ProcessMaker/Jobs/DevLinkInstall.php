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

    const TYPE_INSTALL_BUNDLE = 'install_bundle';

    const TYPE_REINSTALL_BUNDLE = 'reinstall_bundle';

    const TYPE_IMPORT_ASSET = 'import_asset';

    const MODE_UPDATE = 'update';

    const MODE_COPY = 'copy';

    public $tries = 1;

    public $maxExceptions = 1;

    public function __construct(
        public int $userId,
        public int $devLinkId,
        public string $class,
        public int $id,
        public string $importMode,
        public string $type,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //log
        \Log::info('DevLinkInstall job started: ' . $this->devLinkId);
        $devLink = DevLink::findOrFail($this->devLinkId);
        $logger = new Logger($this->userId);

        $lock = Cache::lock(ImportV2::CACHE_LOCK_KEY, ImportV2::RELEASE_LOCK_AFTER);

        if ($lock->get()) {
            DB::transaction(function () use ($devLink, $logger) {
                switch($this->type) {
                    case self::TYPE_INSTALL_BUNDLE:
                        $devLink->logger = $logger;
                        $devLink->installRemoteBundle($this->id, $this->importMode);
                        break;
                    case self::TYPE_REINSTALL_BUNDLE:
                        $bundle = Bundle::findOrFail($this->id);
                        $bundle->reinstall($this->importMode, $logger);
                        break;
                    case self::TYPE_IMPORT_ASSET:
                        $devLink->installRemoteAsset($this->class, $this->id, $logger);
                        break;
                    default:
                        break;
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
