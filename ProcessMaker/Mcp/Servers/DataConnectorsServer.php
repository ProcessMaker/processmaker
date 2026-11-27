<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Tools\Collections\CreateCollectionRecordsTool;
use ProcessMaker\Mcp\Tools\Collections\CreateCollectionTool;
use ProcessMaker\Mcp\Tools\Collections\GetCollectionTool;
use ProcessMaker\Mcp\Tools\Collections\ListCollectionRecordsTool;
use ProcessMaker\Mcp\Tools\Collections\ListCollectionsTool;
use ProcessMaker\Mcp\Tools\Collections\UpdateCollectionTool;
use ProcessMaker\Mcp\Tools\DataConnectors\CreateDataConnectorTool;
use ProcessMaker\Mcp\Tools\DataConnectors\GetDataConnectorTool;
use ProcessMaker\Mcp\Tools\DataConnectors\ListDataConnectorCategoriesTool;
use ProcessMaker\Mcp\Tools\DataConnectors\ListDataConnectorsTool;
use ProcessMaker\Mcp\Tools\DataConnectors\UpdateDataConnectorTool;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Mcp\Tools\GetMcpSkillTool;

#[Name('ProcessMaker Data Connectors')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Manage ProcessMaker Data Connectors and Collections.

Before any other ProcessMaker Data Connectors tool:
1. Call `get-mcp-guidance` for the system prompt and skills catalog.
2. From the catalog, load only skills whose `when` matches the current task via `get-mcp-skill` (or the skill Resource URI).
3. Do not load every skill by default. Follow the system prompt and any skills you loaded.

## Data Connectors
Use list-data-connectors / list-data-connector-categories before creating one.
create-data-connector requires name, type, authtype, and data_source_category_id.
endpoints is a map keyed by purpose; each endpoint needs purpose, method, and url.
Credentials are write-only (not returned by get/list).
Follow the `pm4-data-connector` skill from the ProcessMaker Skills repository (`npx skills add processmaker/skills`),
including BPMN Service Task wiring (implementation `package-data-sources/data-source-task-service`, pm:config dataSource + endpoint).

## Collections
Use list-collections before creating one.
create-collection requires name, description, create_screen_id, read_screen_id, update_screen_id
(create those screens via Screens MCP first when missing).
Creating a collection may auto-create a matching Data Connector for `/collections/{id}/records`.
Add demo rows with create-collection-records (see the `pm4-collection` skill).
MARKDOWN)]
class DataConnectorsServer extends Server
{
    /**
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        GetMcpGuidanceTool::class,
        GetMcpSkillTool::class,
        ListDataConnectorCategoriesTool::class,
        ListDataConnectorsTool::class,
        GetDataConnectorTool::class,
        CreateDataConnectorTool::class,
        UpdateDataConnectorTool::class,
        ListCollectionsTool::class,
        GetCollectionTool::class,
        CreateCollectionTool::class,
        UpdateCollectionTool::class,
        ListCollectionRecordsTool::class,
        CreateCollectionRecordsTool::class,
    ];

    public function createContext(): ServerContext
    {
        app()->instance(McpGuidance::SKILL_SCOPE_KEY, McpGuidance::SCOPE_DATA_CONNECTORS);

        $guidance = app(McpGuidance::class);
        $this->resources = McpServerSupport::skillResources($guidance);

        $context = parent::createContext();
        $appendix = $guidance->instructionsAppendix();
        if ($appendix !== '') {
            $context->instructions = rtrim($context->instructions) . "\n\n" . $appendix;
        }

        return $context;
    }
}
