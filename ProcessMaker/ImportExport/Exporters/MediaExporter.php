<?php

namespace ProcessMaker\ImportExport\Exporters;

class MediaExporter extends ExporterBase
{
    public $discard = true;

    public function export(): void
    {
        // Add the dependent of the media.
    }

    public function import(): bool
    {
        $media = $this->model;

        return $media->saveOrFail();
    }
}
