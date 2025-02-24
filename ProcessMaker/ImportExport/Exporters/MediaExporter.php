<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;

class MediaExporter extends ExporterBase
{
    public function export(): void
    {
        try {
            $requestPublic = $this->getPublicProcess();
            if ($requestPublic->id === $this->model->process_request_id) {
                $this->addReference(DependentType::PUBLIC_FILE, true);
            } else {
                $this->addReference(DependentType::PUBLIC_FILE, false);
            }
        } catch (\Exception $e) {
            // Si no existe el proceso público, asumimos que es un archivo normal
            $this->addReference(DependentType::PUBLIC_FILE, false);
        }

        if (File::exists($this->model->getPath())) {
            $this->addReference(DependentType::MEDIA, [
                'base64' => base64_encode(file_get_contents($this->model->getPath())),
            ]);
        }
    }

    public function import(): bool
    {
        try {
            if ($this->getReference(DependentType::PUBLIC_FILE)) {
                $requestPublic = $this->getPublicProcess();
                $this->model->model = $requestPublic;
            }
        } catch (\Exception $e) {
            // Si no existe el proceso público, continuamos con el modelo original
        }

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

    public function getPublicProcess(): ProcessRequest
    {
        return Process::where('package_key', 'package-files/public-files')
            ->firstOrFail()
            ->requests()
            ->firstOrFail();
    }
}
