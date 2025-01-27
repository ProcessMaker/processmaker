<?php

namespace Tests\Model;

use ProcessMaker\Exception\ExporterNotSupported;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

final class BundleAssetTest extends TestCase
{
    public function testCanExport(): void
    {
        $screen = Screen::factory()->create();

        $this->assertTrue(BundleAsset::canExport($screen));
    }

    public function testExporterNotSupported(): void
    {
        $group = Group::factory()->create();
        $bundle = Bundle::factory()->create();

        $this->expectException(ExporterNotSupported::class);
        $bundle->addAsset($group);
    }
}
