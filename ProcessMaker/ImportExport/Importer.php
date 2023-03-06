<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Importer
{
    public $options;

    public $payload;

    public $manifest;

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

    public function doImport()
    {
        DB::transaction(function () {
            // First, we save the model so we have IDs set for all assets
            Schema::disableForeignKeyConstraints();
            foreach ($this->manifest->all() as $exporter) {
                if ($exporter->mode !== 'discard') {
                    if ($exporter->disableEventsWhenImporting) {
                        $exporter->model->saveQuietly();
                    } else {
                        $exporter->model->save();
                    }
                    $exporter->log('newId', $exporter->model->id);
                }
            }
            Schema::enableForeignKeyConstraints();

            // Now, run the import method in each Exporter class
            foreach ($this->manifest->all() as $exporter) {
                if ($exporter->mode !== 'discard') {
                    $exporter->runImport();
                }
            }

            $this->manifest->runAfterImport();
        });

        return $this->manifest->all();
    }
}
