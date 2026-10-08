<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\ImportExport\DependentType;

class ScreenTemplatesExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public function export() : void
    {
        $media = $this->model->getMedia($this->model->media_collection);

        $this->addReference(DependentType::MEDIA, $media->map(function ($item) {
            return [
                'file_name' => $item->file_name,
                'custom_properties' => $item->custom_properties,
                'base64' => base64_encode(file_get_contents($item->getPath())),
            ];
        })->all());
    }

    public function import() : bool
    {
        $screenTemplate = $this->model;
        $screenTemplate->user_id = Auth::user()->id;
        $screenTemplate->is_default_template = 0;
        $screenTemplate->media_collection = 'st-' . $screenTemplate->uuid . '-media';
        $screenTemplate->save();
        $screenTemplate->clearMediaCollection($screenTemplate->media_collection);

        foreach ($this->getReference(DependentType::MEDIA) ?? [] as $item) {
            if (empty($item['base64'])) {
                continue;
            }

            $screenTemplate->addMediaFromBase64($item['base64'])
                ->usingFileName($item['file_name'])
                ->withCustomProperties($item['custom_properties'] ?? [])
                ->toMediaCollection($screenTemplate->media_collection);
        }

        return true;
    }
}
