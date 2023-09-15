<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Script;

class Importer
{
    public $options;

    public $payload;

    public $manifest;

    public $newScriptId;

    public function __construct(array $payload, Options $options)
    {
        $this->payload = $payload;
        $this->options = $options;
        $this->manifest = $this->loadManifest();
    }

    public function previewImport()
    {
        return $this->manifest->toArray(true);
    }

    public function loadManifest()
    {
        return Manifest::fromArray($this->payload['export'], $this->options);
    }

    public function doImport($existingAssetInDatabase = null)
    {
        DB::transaction(function () use ($existingAssetInDatabase) {
            // First, we save the model so we have IDs set for all assets
            Schema::disableForeignKeyConstraints();
            foreach ($this->manifest->all() as $exporter) {
                if ($exporter->mode !== 'discard') {
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
                    $exporter->runImport($existingAssetInDatabase);
                }
            }

            $this->manifest->runAfterImport();
        });

        return $this->manifest->all();
    }
}
