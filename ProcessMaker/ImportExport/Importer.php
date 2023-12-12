<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use ProcessMaker\Exception\ImportPasswordException;
use ProcessMaker\Models\Script;

class Importer
{
    public $options;

    public $payload;

    public $manifest;

    public $newScriptId;

    public $logger;

    public function __construct(array $payload, Options $options, $logger = null)
    {
        $this->payload = $payload;
        $this->options = $options;
        $this->logger = $logger ?? new Logger();
        $this->manifest = $this->loadManifest();
    }

    public function previewImport()
    {
        return $this->manifest->toArray(true);
    }

    public function loadManifest()
    {
        return Manifest::fromArray($this->payload['export'], $this->options, $this->logger);
    }

    public function doImport($existingAssetInDatabase = null)
    {
        $this->logger->log('Starting Transaction');
        DB::transaction(function () use ($existingAssetInDatabase) {
            // First, we save the model so we have IDs set for all assets
            Schema::disableForeignKeyConstraints();

            $count = count(Arr::where($this->manifest->all(), fn ($exporter) => $exporter->mode !== 'discard'));
            $this->logger->log("Importing $count assets");

            foreach ($this->manifest->all() as $exporter) {
                if ($exporter->mode !== 'discard') {
                    $this->logger->log('Importing ' . get_class($exporter->model));
                    if ($exporter->disableEventsWhenImporting) {
                        $exporter->model->saveQuietly();
                    } else {
                        $exporterClass = get_class($exporter->model);
                        if ($exporterClass === 'ProcessMaker\Packages\Connectors\DataSources\Models\Script') {
                            switch ($exporter->mode) {
                                case 'copy':
                                    $exporter->model->script_id = $this->newScriptId;
                                    break;
                                case 'update':
                                    $script = Script::find($exporter->model->script_id);
                                    if ($script) {
                                        $script->fill($exporter->model->getAttributes());
                                        $script->save();
                                    }
                                    break;

                                default:
                                    // code...
                                    break;
                            }
                        }

                        $exporter->model->save();

                        if ($exporterClass === 'ProcessMaker\Models\Script') {
                            $this->newScriptId = $exporter->model->id;
                        }
                    }
                    $exporter->log('newId', $exporter->model->id);
                }
            }
            Schema::enableForeignKeyConstraints();

            // Now, run the import method in each Exporter class
            foreach ($this->manifest->all() as $exporter) {
                if ($exporter->mode !== 'discard') {
                    $this->logger->log('Associating ' . get_class($exporter->model));
                    $exporter->runImport($existingAssetInDatabase);
                }
            }

            $this->manifest->runAfterImport();
        });

        $manifest = $this->manifest->all();
        $newProcessId = $manifest[$this->payload['root']]->log['newId'];

        $this->logger->log('Done Importing', ['processId' => $newProcessId, 'message' => self::getMessages()]);

        return $manifest;
    }

    public static function getMessages()
    {
        $message = null;
        if (Session::get('_alert')) {
            $message = Session::get('_alert');
        }

        return $message;
    }

    public static function handlePasswordDecrypt(array $payload, string|null $password)
    {
        if (isset($payload['encrypted']) && $payload['encrypted']) {
            if (!$password) {
                throw new ImportPasswordException('password required');
            }

            $payload = (new ExportEncrypted($password))->decrypt($payload);

            if ($payload['export'] === null) {
                throw new ImportPasswordException('incorrect password');
            }
        }

        return $payload;
    }

    public static function cleanOldFiles()
    {
        collect(Storage::listContents('import', true))->each(function ($file) {
            if ($file['type'] == 'file' && $file['timestamp'] < now()->subDays(15)->getTimestamp()) {
                Storage::disk('public')->delete($file['path']);
            }
        });
    }
}
