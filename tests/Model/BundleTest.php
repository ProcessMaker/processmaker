<?php

namespace Tests\Model;

use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class BundleTest extends TestCase
{
    use HelperTrait;

    public function testExport()
    {
        $this->addGlobalSignalProcess();

        $process = Process::factory()->create();
        $screen = Screen::factory()->create();
        $bundle = Bundle::factory()->create();

        BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $process->id,
        ]);

        BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Screen::class,
            'asset_id' => $screen->id,
        ]);

        $payload = $bundle->export();

        $this->assertEquals(2, count($payload));
        $this->assertEquals($process->name, $payload[0]['name']);
        $this->assertEquals($screen->title, $payload[1]['name']);
    }

    public function testSyncAssets()
    {
        $screen1 = Screen::factory()->create(['title' => 'Screen 1']);
        $screen2 = Screen::factory()->create(['title' => 'Screen 2']);
        $screen3 = Screen::factory()->create(['title' => 'Screen 3']);
        $bundle = Bundle::factory()->create();

        $bundle->syncAssets([$screen1, $screen2]);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals($screen1->id, $bundle->assets[0]->asset_id);
        $this->assertEquals($screen2->id, $bundle->assets[1]->asset_id);

        $bundle->syncAssets([$screen1, $screen3]);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals($screen1->id, $bundle->assets[0]->asset_id);
        $this->assertEquals($screen3->id, $bundle->assets[1]->asset_id);
    }
}
