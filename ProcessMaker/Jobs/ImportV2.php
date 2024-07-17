<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use Throwable;

class ImportV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $maxExceptions = 1;

    const CACHE_LOCK_KEY = 'import_running';

    const RELEASE_LOCK_AFTER = 3600; // 1 hour

    const FILE_PATH = 'import/payload.json';

    const OPTIONS_PATH = 'import/options.json';

    const MANIFEST_PATH = 'import/manifest.json';

    const DEBUG_ZIP_PATH = 'import/debug.zip';

    const LOG_PATH = 'import/log.txt';

    public function __construct(
        public int $userId,
        public string|null $password,
        public string $hash,
        public bool $isPreview
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = new Logger($this->userId);

        $lock = Cache::lock(self::CACHE_LOCK_KEY, self::RELEASE_LOCK_AFTER);

        if ($lock->get()) {
            $logger->clear();
            $this->runImport($logger);
            $lock->release();
        } else {
            // Don't throw exception because that will unlock the running job
            $logger->error('Already running!');
        }
    }

    private function runImport($logger)
    {
        $this->checkHash();

        $payload = json_decode(Storage::get(self::FILE_PATH), true);

        $options = [];
        if (!$this->isPreview) {
            $options = json_decode(Storage::get(self::OPTIONS_PATH), true);
        }

        try {
            $payload = Importer::handlePasswordDecrypt($payload, $this->password);
        } catch (ImportPasswordException $e) {
            $logger->exception($e);

            return;
        }

        $options = new Options($options);
        $importer = new Importer($payload, $options, $logger);
        if ($this->isPreview) {
            $manifest = $importer->previewImport();
            Storage::put(self::MANIFEST_PATH, json_encode($manifest));

            $logger->log('preview', [
                'rootUuid' => $payload['root'],
                'processVersion' => 2,
            ], 200);
        } else {
            $importer->doImport();
        }

        $logger->log('Done');
    }

    public function failed(Throwable $exception): void
    {
        (new Logger($this->userId))->exception($exception);

        // Unlock the job
        // We can't use $this->lock->release() here because this is run in a new instance
        Cache::lock(self::CACHE_LOCK_KEY)->forceRelease();
    }

    public static function isRunning()
    {
        return (bool) Cache::lockConnection()->client()->get(Cache::getPrefix() . 'import_running');
    }

    private function checkHash()
    {
        if ($this->hash !== md5_file(Storage::path(self::FILE_PATH))) {
            throw new \Exception('File hash does not match. Is another user importing?');
        }
    }
}
