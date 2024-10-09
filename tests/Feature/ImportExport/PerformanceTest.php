<?php

namespace Tests\Feature\ImportExport;

use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\Models\User;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    public function testImportPerformance()
    {
        User::factory()->create(['is_administrator' => true]);

        $payload = json_decode(
            file_get_contents(
                'compress.zlib://' .
                base_path('tests/Fixtures/ImportExport/payload.json.gz')
            ),
            true
        );
        $optionsItems = json_decode(
            file_get_contents(
                'compress.zlib://' .
                base_path('tests/Fixtures/ImportExport/options.json.gz')
            ),
            true
        );

        $options = new Options($optionsItems);
        $importer = new Importer($payload, $options);

        $queryCount = 0;
        \DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        $startTime = microtime(true);
        $importer->doImport();
        $totalTime = microtime(true) - $startTime;

        echo "\nImport took {$totalTime} seconds and had {$queryCount} queries\n";

        $this->assertTrue(true);
    }
}
