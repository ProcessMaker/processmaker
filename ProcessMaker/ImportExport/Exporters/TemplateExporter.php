<?php

namespace ProcessMaker\ImportExport\Exporters;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ProcessMaker\Assets\ScreensInScreen;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\Script;

class TemplateExporter extends ExporterBase
{
    public function export() : void
    {
    }

    public function import() : bool
    {
    }
}
