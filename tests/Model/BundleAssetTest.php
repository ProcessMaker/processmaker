<?php

namespace Tests\Model;

use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Process;
use Tests\TestCase;

class BundleAssetTest extends TestCase
{
    public function testExporterClass()
    {
        $bundleAsset = BundleAsset::factory()->create([
            'asset_type' => Process::class,
        ]);

        $exporterClass = $bundleAsset->exporterClass();

        $this->assertEquals(ProcessExporter::class, $exporterClass);
    }

    public function testExporterNotSupported()
    {
        $bundleAsset = BundleAsset::factory()->create([
            'asset_type' => 'foo',
        ]);

        $this->expectException(ExporterNotSupported::class);
        $bundleAsset->exporterClass();
    }
}
