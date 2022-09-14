<?php

namespace Tests;

use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ExporterTest extends TestCase
{
    public function testExporter()
    {
        $screen = factory(Screen::class)->create();
        $screenCategory1 = factory(ScreenCategory::class)->create();
        $screenCategory2 = factory(ScreenCategory::class)->create();
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;
        $screen->save();

        $exporter = new Exporter();
        $exporter->exportScreen($screen);

        // WIP
        echo "\n";
        foreach ($exporter->manifest->getAll() as $uuid => $manifestExporter) {
            echo "$uuid -- " . get_class($manifestExporter->model) . "\n";
        }
        print_r($exporter->tree());
    }
}
