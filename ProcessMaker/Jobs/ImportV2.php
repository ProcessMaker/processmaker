<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\ImportExport\Options;
use Throwable;

class ImportV2 implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public int $userId,
        public string $filePath,
        public string|null $optionsPath,
        public string|null $password,
        public bool $isPreview
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = json_decode(Storage::get($this->filePath), true);

        $options = [];
        if ($this->optionsPath) {
            $options = json_decode(Storage::get($this->optionsPath), true);
        }

        $logger = new Logger($this->userId);

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
            $manifestPath = Storage::put('imports/' . uniqid('manifest_') . '.json', json_encode($manifest));

            $logger->log('preview', [
                'filePath' => $this->filePath,
                'manifestPath' => $manifestPath,
                'rootUuid' => $payload['root'],
                'processVersion' => 2,
            ], 200);
        } else {
            $manifest = $importer->doImport();
        }
    }

    public function failed(Throwable $exception): void
    {
        (new Logger($this->userId))->exception($exception);
    }
}
