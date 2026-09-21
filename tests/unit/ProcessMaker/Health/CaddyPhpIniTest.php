<?php

declare(strict_types=1);

namespace Tests\Unit\ProcessMaker\Health;

use Illuminate\Support\Facades\Config;
use ProcessMaker\Health\CaddyPhpIni;
use Tests\TestCase;

class CaddyPhpIniTest extends TestCase
{
    public function test_it_maps_caddy_env_to_php_ini_directives(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '2000M',
            'OCTANE_MAX_EXECUTION_TIME' => 90,
        ]);

        $configured = CaddyPhpIni::configured();

        $this->assertSame('2000M', $configured['memory_limit']);
        $this->assertSame('90', $configured['max_execution_time']);
        $this->assertArrayNotHasKey('xdebug.mode', $configured);
    }

    public function test_it_verifies_worker_values_against_configuration(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MEMORY_LIMIT' => '2000M',
            'OCTANE_MAX_EXECUTION_TIME' => 90,
        ]);

        $verification = CaddyPhpIni::verify([
            'memory_limit' => '2000M',
            'max_execution_time' => '90',
        ]);

        $this->assertTrue(CaddyPhpIni::allMatch([
            'memory_limit' => '2000M',
            'max_execution_time' => '90',
        ]));

        $this->assertFalse(CaddyPhpIni::allMatch([
            'memory_limit' => '128M',
            'max_execution_time' => '90',
        ]));

        $this->assertTrue($verification[0]['matches']);
    }

    public function test_it_accepts_frankenphp_worker_overrides_as_equivalent(): void
    {
        Config::set('octane.caddy.env', [
            'OCTANE_MAX_EXECUTION_TIME' => 90,
            'OCTANE_MAX_INPUT_TIME' => 90,
        ]);

        $this->assertTrue(CaddyPhpIni::directiveMatches('max_execution_time', '90', '0'));
        $this->assertTrue(CaddyPhpIni::directiveMatches('max_input_time', '90', '-1'));
    }
}
