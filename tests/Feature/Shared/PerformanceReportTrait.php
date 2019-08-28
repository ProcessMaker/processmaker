<?php

namespace Tests\Feature\Shared;

trait PerformanceReportTrait
{
    private static $measurements = [];

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
