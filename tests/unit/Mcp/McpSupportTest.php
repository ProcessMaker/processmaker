<?php

namespace Tests\Unit\Mcp;

use ProcessMaker\Mcp\McpSupport;
use Tests\TestCase;

class McpSupportTest extends TestCase
{
    /**
     * Fails early in CI when PM4 core changed and MCP integration points no longer match.
     */
    public function testMcpIsCompatibleWithPm4Core(): void
    {
        $report = McpSupport::report();

        $this->assertSame(McpSupport::MCP_VERSION, $report['mcp_version']);
        $this->assertNotEmpty($report['supported_features']);

        $this->assertFalse(
            $report['platform_drift_detected'],
            $this->platformDriftFailureMessage($report),
        );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function platformDriftFailureMessage(array $report): string
    {
        $drift = $report['platform_drift'] ?? [];

        if ($drift === []) {
            return 'platform_drift_detected is true but platform_drift is empty. Review McpSupport::report().';
        }

        $lines = [
            'MCP is behind PM4 core. Update ProcessMaker/Mcp and McpSupport::platformChecks().',
            'PM version: ' . ($report['pm_version'] ?? 'unknown'),
            'Drift:',
        ];

        foreach ($drift as $item) {
            $lines[] = sprintf('  - %s: %s', $item['id'] ?? 'unknown', $item['detail'] ?? '');
        }

        $lines[] = 'Agent tool: get_mcp_capabilities';

        return implode("\n", $lines);
    }
}
