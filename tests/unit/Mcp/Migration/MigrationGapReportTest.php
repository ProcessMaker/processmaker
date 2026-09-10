<?php

namespace Tests\unit\Mcp\Migration;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Mcp\Migration\MigrationGapReport;

class MigrationGapReportTest extends TestCase
{
    public function testBuildMarksMissingVariablesAndValidation(): void
    {
        $report = (new MigrationGapReport())->build(
            ['variables' => [['var_name' => 'total']]],
            ['validation' => ['valid' => false], 'applied_variables' => []]
        );

        $this->assertFalse($report['ready']);
        $this->assertNotEmpty($report['gaps']);
    }
}
