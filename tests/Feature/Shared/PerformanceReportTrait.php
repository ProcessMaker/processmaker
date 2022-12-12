<?php

namespace Tests\Feature\Shared;

use Illuminate\Support\Facades\Artisan;
use Mockery;

trait PerformanceReportTrait
{
    private static $measurements = [];

    public function setUpSkip()
    {
        $this->markTestSkipped('FOUR-6653');
    }

    public function setUpMockArtisan()
    {
        // Prevent saved search user observer from running artisan in test
        Artisan::shouldReceive('call')->with('package-savedsearch:add-defaults', Mockery::any());
    }

    private function addMeasurement($name, $measure)
    {
        if (!isset(static::$measurements[$name])) {
            static::$measurements[$name] = [];
        }
        static::$measurements[$name][] = $measure;
    }

    private function writeReport($name, $output, $tpl)
    {
        $report = fopen($output, 'w');
        ob_start();
        $measurements = self::$measurements[$name];
        include __DIR__ . '/' . $tpl;
        fwrite($report, ob_get_clean());
        fclose($report);
    }
}
