<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\DependentType;

class MediaExporter extends ExporterBase
{
    public function export(): void
    {
        if (File::exists($this->model->getPath())) {
            $this->addReference(DependentType::MEDIA, [
                'base64' => base64_encode(file_get_contents($this->model->getPath())),
            ]);
        }
    }

    public function import(): bool
    {
        $ref = $this->getReference(DependentType::MEDIA);
        if ($ref && isset($ref['base64'])) {
            $this->model->model->addMediaFromBase64($ref['base64'])
                ->usingFileName($this->model->file_name)
                ->withCustomProperties($this->model->custom_properties)
                ->toMediaCollection($this->model->collection_name);
        }

        // We should delete the model, because the Spatie library recreates it.
        $this->model->delete();

        return true;
    }
}
