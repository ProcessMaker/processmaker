<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\File;
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
        if (File::exists($file)) {
            $media->base64 = base64_encode(file_get_contents($file));
        }

        $this->addReference(DependentType::MEDIA, $media->toArray());
    }

    public function import(): bool
    {
        $this->model->save();
        $mediaModel = $this->model->refresh();

        $mediaElement = $this->getReference(DependentType::MEDIA);
        if (isset($mediaElement['base64'])) {
            $modelWithMedia = $mediaModel->model;
            $modelWithMedia->addMediaFromBase64($mediaElement['base64'])
                ->usingFileName($mediaElement['file_name'])
                ->toMediaCollection($mediaElement['collection_name']);
        }

        // We should delete the model, because the Spatie library recreates it.
        $mediaModel->delete();

        return true;
    }
}
