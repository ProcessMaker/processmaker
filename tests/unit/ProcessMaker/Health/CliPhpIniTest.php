<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Health;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Health\CaddyPhpIni;
use ProcessMaker\Health\CliPhpIni;
use Tests\TestCase;

class CliPhpIniTest extends TestCase
{
    public function test_it_builds_versus_rows_between_cli_and_worker(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '2000M',
            'OCTANE_MAX_EXECUTION_TIME' => 90,
            'OCTANE_MAX_INPUT_TIME' => 90,
        ]);

        $rows = CliPhpIni::versusRows(
            [
                'memory_limit' => '-1',
                'max_execution_time' => '0',
                'max_input_time' => '-1',
            ],
            [
                'memory_limit' => '2000M',
                'max_execution_time' => '0',
                'max_input_time' => '-1',
            ],
            CaddyPhpIni::configured(),
        );

        $memory = collect($rows)->firstWhere('directive', 'memory_limit');
        $execution = collect($rows)->firstWhere('directive', 'max_execution_time');

        $this->assertTrue($memory['cli_differs_from_worker']);
        $this->assertTrue($execution['worker_matches_configured']);
        $this->assertFalse($execution['cli_differs_from_worker']);
    }

    public function test_it_builds_extension_rows(): void
    {
        $rows = CliPhpIni::extensionRows(
            ['rdkafka' => true, 'mongodb' => false],
            ['rdkafka' => false, 'mongodb' => false],
        );

        $rdkafka = collect($rows)->firstWhere('extension', 'rdkafka');

        $this->assertTrue($rdkafka['differs']);
        $this->assertSame('yes', $rdkafka['cli']);
        $this->assertSame('no', $rdkafka['worker']);
    }

    public function test_it_marks_worker_extensions_unknown_when_unavailable(): void
    {
        $rows = CliPhpIni::extensionRows(['redis' => true], null);

        $redis = collect($rows)->firstWhere('extension', 'redis');

        $this->assertSame('unknown', $redis['worker']);
        $this->assertFalse($redis['differs']);
    }

    public function test_it_filters_baseline_diff_rows(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '2000M',
            'OCTANE_MAX_INPUT_VARS' => 9000,
        ]);

        $rows = CliPhpIni::versusRows(
            ['memory_limit' => '2000M', 'max_input_vars' => '1000'],
            ['memory_limit' => '2000M', 'max_input_vars' => '9000'],
            CaddyPhpIni::configured(),
        );

        $diffs = CliPhpIni::baselineDiffRows($rows);

        $this->assertCount(1, $diffs);
        $this->assertSame('max_input_vars', $diffs[0]['directive']);
    }

    public function test_it_formats_unlimited_ini_values(): void
    {
        $this->assertSame('unlimited (0)', CliPhpIni::formatIniValue('max_execution_time', '0'));
        $this->assertSame('90 sec', CliPhpIni::formatIniValue('max_execution_time', '90'));
        $this->assertSame('unlimited', CliPhpIni::formatIniValue('memory_limit', '-1'));
    }

    public function test_it_parses_loaded_ini_path_from_php_ini_output(): void
    {
        $output = <<<'TXT'
Configuration File (php.ini) Path: /etc/php/8.5/cli
Loaded Configuration File:         /etc/php/8.5/cli/php.ini
Scan for additional .ini files in: /etc/php/8.5/cli/conf.d
TXT;

        $this->assertSame('/etc/php/8.5/cli/php.ini', CliPhpIni::loadedIniPathFromPhpIniOutput($output));
        $this->assertNull(CliPhpIni::loadedIniPathFromPhpIniOutput("Loaded Configuration File:         (none)\n"));
    }
}
