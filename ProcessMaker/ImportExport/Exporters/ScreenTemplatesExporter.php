<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Facades\Auth;

class ScreenTemplatesExporter extends ExporterBase
{
    public $handleDuplicatesByIncrementing = ['name'];

    public function export() : void
    {
    }

    public function import() : bool
    {
        $screenTemplate = $this->model;
        $screenTemplate->user_id = Auth::user()->id;
        $screenTemplate->is_default_template = 0;
        $screenTemplate->save();

        return true;
    }
}
