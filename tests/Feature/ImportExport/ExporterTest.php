<?php

namespace Tests;

use Illuminate\Support\Arr;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;

class ExporterTest extends TestCase
{
    public function testExportScreen()
    {
        $screen = factory(Screen::class)->create();
        $screenCategory1 = factory(ScreenCategory::class)->create();
        $screenCategory2 = factory(ScreenCategory::class)->create();
        $screen->screen_category_id = $screenCategory1->id . ',' . $screenCategory2->id;
        $screen->save();

        $exporter = new Exporter();
        $tree = $exporter->exportScreen($screen);

        $this->assertEquals($screen->uuid, Arr::get($tree, '0.uuid'));
        $this->assertEquals($screenCategory1->uuid, Arr::get($tree, '0.dependents.0.uuid'));
        $this->assertEquals($screenCategory2->uuid, Arr::get($tree, '0.dependents.1.uuid'));
    }
}
