<?php

namespace Tests;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ImportTest extends TestCase
{
    public function testExportImport()
    {
        $screen = factory(Screen::class)->create();
        $screenCategory1 = factory(ScreenCategory::class)->create();
        $screenCategory2 = factory(ScreenCategory::class)->create();
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;
        $screen->save();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);
        $payload = $exporter->payload();

        $screenCategory2->delete();
        
        $options = new Options([]);
        $importer = new Importer($options);
        
        $manifestArray = $importer->prviewImport($payload);
        $tree = $importer->tree($payload);
        // WIP
        dd($tree);
    }
}
