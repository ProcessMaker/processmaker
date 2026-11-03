<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use ProcessMaker\Mcp\McpSupport;
use ProcessMaker\Mcp\Migration\Tools\ApplyAbeConfigurationTool;
use ProcessMaker\Mcp\Migration\Tools\ApplyMigrationBundleTool;
use ProcessMaker\Mcp\Migration\Tools\ApplyTaskAssignmentsTool;
use ProcessMaker\Mcp\Migration\Tools\CreateCollectionFromReportTableTool;
use ProcessMaker\Mcp\Migration\Tools\CreateProcessTool;
use ProcessMaker\Mcp\Migration\Tools\CreateScreenFromDynaformTool;
use ProcessMaker\Mcp\Migration\Tools\CreateScreenTool;
use ProcessMaker\Mcp\Migration\Tools\CreateScriptTool;
use ProcessMaker\Mcp\Migration\Tools\GetMigrationGapReportTool;
use ProcessMaker\Mcp\Migration\Tools\GetMigrationLogTool;
use ProcessMaker\Mcp\Migration\Tools\LinkMigrationAssetsTool;
use ProcessMaker\Mcp\Migration\Tools\LinkScreenToTaskTool;
use ProcessMaker\Mcp\Migration\Tools\LinkScriptToTaskTool;
use ProcessMaker\Mcp\Migration\Tools\ListMigrationLogsTool;
use ProcessMaker\Mcp\Migration\Tools\ResumeMigrationBundleTool;
use ProcessMaker\Mcp\Migration\Tools\UpdateProcessBpmnTool;
use ProcessMaker\Mcp\Migration\Tools\ValidateProcessTool;
use ProcessMaker\Mcp\Process\Tools\AddBpmnExclusiveGatewayTool;
use ProcessMaker\Mcp\Process\Tools\AddBpmnSequenceFlowTool;
use ProcessMaker\Mcp\Process\Tools\AddBpmnUserTaskTool;
use ProcessMaker\Mcp\Process\Tools\AnalyzeProcessTool;
use ProcessMaker\Mcp\Process\Tools\ApplyProcessDesignTool;
use ProcessMaker\Mcp\Process\Tools\ApplyProcessVariablesTool;
use ProcessMaker\Mcp\Process\Tools\CreateScreenFromFieldsTool;
use ProcessMaker\Mcp\Process\Tools\GetMcpCapabilitiesTool;
use ProcessMaker\Mcp\Process\Tools\GetProcessBpmnTool;
use ProcessMaker\Mcp\Process\Tools\GetProcessInventoryTool;
use ProcessMaker\Mcp\Process\Tools\GetProcessTool;
use ProcessMaker\Mcp\Process\Tools\GetScreenTool;
use ProcessMaker\Mcp\Process\Tools\GetScriptTool;
use ProcessMaker\Mcp\Process\Tools\ListProcessesTool;
use ProcessMaker\Mcp\Process\Tools\ListScreensTool;
use ProcessMaker\Mcp\Process\Tools\ListScriptsTool;
use ProcessMaker\Mcp\Process\Tools\UpdateProcessTool;

#[Name('processmaker-agent')]
#[Version(McpSupport::MCP_VERSION)]
#[Instructions('ProcessMaker 4 MCP agent. Modes: (1) Migration PM3→PM4: pm3-migration-reader + apply_migration_bundle/resume. (2) Design: apply_process_design or create_process + create_screen_from_fields + link tools. (3) Improve: get_process_inventory, analyze_process, then fix with BPMN/link tools. Always call get_mcp_capabilities after PM4 upgrades. Ask user confirmation before writes. Not full UI parity: no case runner, no user sync, BPMN layout not adjusted.')]
class WriterServer extends Server
{
    /** Cursor and most MCP clients do not paginate tools/list; expose all tools in one page. */
    public int $maxPaginationLength = 100;

    public int $defaultPaginationLength = 100;

    protected array $tools = [
        // Keep migration + diagnostics in the first MCP page (clients often paginate at 15).
        GetMcpCapabilitiesTool::class,
        ApplyMigrationBundleTool::class,
        ResumeMigrationBundleTool::class,
        GetMigrationLogTool::class,
        ListMigrationLogsTool::class,
        GetMigrationGapReportTool::class,
        CreateScreenFromDynaformTool::class,
        LinkMigrationAssetsTool::class,
        ApplyTaskAssignmentsTool::class,
        ApplyAbeConfigurationTool::class,
        CreateCollectionFromReportTableTool::class,
        // Read
        ListProcessesTool::class,
        GetProcessTool::class,
        GetProcessInventoryTool::class,
        GetProcessBpmnTool::class,
        ListScreensTool::class,
        GetScreenTool::class,
        ListScriptsTool::class,
        GetScriptTool::class,
        AnalyzeProcessTool::class,
        // Design / write
        CreateProcessTool::class,
        UpdateProcessTool::class,
        CreateScreenTool::class,
        CreateScreenFromFieldsTool::class,
        CreateScriptTool::class,
        ApplyProcessDesignTool::class,
        ApplyProcessVariablesTool::class,
        AddBpmnUserTaskTool::class,
        AddBpmnSequenceFlowTool::class,
        AddBpmnExclusiveGatewayTool::class,
        ValidateProcessTool::class,
        UpdateProcessBpmnTool::class,
        LinkScreenToTaskTool::class,
        LinkScriptToTaskTool::class,
    ];

    protected function boot(): void
    {
        $this->addMethod('tools/list', ListAllTools::class);
    }
}
