<?php

namespace Tests\Model;

use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class BundleAssetTest extends TestCase
{
    public function testCanExport()
    {
        $screen = Screen::factory()->create();

        $this->assertTrue(BundleAsset::canExport($screen));
        $this->assertFalse(BundleAsset::canExport(null));
    }

    public function testExporterNotSupported()
    {
        $group = Group::factory()->create();
        $bundle = Bundle::factory()->create();

        $this->expectException(ExporterNotSupported::class);
        $bundle->addAsset($group);
    }

    public function testMissingAssetCanBeSerialized()
    {
        $bundle = Bundle::factory()->create();
        $missingProcessId = Process::max('id') + 1000;
        $bundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $missingProcessId,
        ]);

        $serialized = $bundleAsset->toArray();

        $this->assertSame(BundleAsset::INTEGRITY_MISSING, $serialized['integrity_status']);
        $this->assertSame("Missing Process #$missingProcessId", $serialized['name']);
        $this->assertNull($serialized['url']);
        $this->assertNull($serialized['owner_name']);
        $this->assertSame([], $serialized['categories']);
    }

    public function testUnavailableAssetTypeCanBeSerialized()
    {
        $bundleAsset = BundleAsset::factory()->create([
            'asset_type' => 'ProcessMaker\Missing\Asset',
            'asset_id' => 1234,
        ]);

        $serialized = $bundleAsset->toArray();

        $this->assertSame(BundleAsset::INTEGRITY_TYPE_UNAVAILABLE, $serialized['integrity_status']);
        $this->assertSame('Missing Asset #1234', $serialized['name']);
        $this->assertNull($serialized['url']);
    }

    public function testDeletingAnAssetLeavesItsBundleAssociationForManualRepair()
    {
        $process = Process::factory()->create();
        $bundleAsset = BundleAsset::factory()->create([
            'asset_type' => Process::class,
            'asset_id' => $process->id,
        ]);

        $process->delete();

        $this->assertTrue($process->trashed());
        $this->assertDatabaseHas('bundle_assets', ['id' => $bundleAsset->id]);
        $this->assertSame(
            BundleAsset::INTEGRITY_MISSING,
            $bundleAsset->refresh()->integrity_status
        );
    }
}
