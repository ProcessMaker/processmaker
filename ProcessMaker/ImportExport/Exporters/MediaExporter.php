<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\DependentType;

class MediaExporter extends ExporterBase
{
    public $discard = false;

    public function export(): void
    {
        $media = $this->model;
        $filePath = $media->id . '/' . $media->file_name;
        $file = Storage::disk('public')->path($filePath);
        $media->base64 = base64_encode(file_get_contents($file));

        $this->addReference(DependentType::MEDIA, $media->toArray());
    }

    public function import(): bool
    {
        $mediaElement = $this->getReference(DependentType::MEDIA);
        $modelWithMedia = $this->model->model_type::findOrFail($this->model->model_id);

        $modelWithMedia->addMediaFromBase64($mediaElement['base64'])
            ->usingFileName($mediaElement['file_name'])
            ->toMediaCollection($mediaElement['collection_name']);

        return true;
    }
}
