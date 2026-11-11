<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Tools\FlowGenies\CreateFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\DeleteFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\GetFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ListFlowGenieCategoriesTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ListFlowGeniesTool;
use ProcessMaker\Mcp\Tools\FlowGenies\UpdateFlowGenieTool;
use ProcessMaker\Mcp\Tools\FlowGenies\ValidateFlowGenieTool;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Mcp\Tools\GetMcpSkillTool;

#[Name('ProcessMaker FlowGenies')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Manage ProcessMaker FlowGenie assets (package-ai agent definitions).

Before any other ProcessMaker FlowGenies tool:
1. Call `get-mcp-guidance` for the system prompt and skills catalog.
2. From the catalog, load only skills whose `when` matches the current task via `get-mcp-skill` (or the skill Resource URI).
3. Do not load every skill by default. Follow the system prompt and any skills you loaded.

Use list-flow-genies before creating one.
Use list-flow-genie-categories to pick flow_genie_category_id.
Call validate-flow-genie before create/update when unsure about config shape.
create-flow-genie requires name, description, and flow_genie_category_id.
config is the agent JSON (instructions, model, provider, output_type, tools, mcp_servers, …).
Secrets (provider_api_key, MCP tokens) are write-only — get/list redact them.
After creating a FlowGenie, wire it into BPMN with Processes MCP skill `process-connector-flowgenie`
(`implementation` `package-ai/processmaker-ai-task`, pm:config `flow_genie_id` + `output_variable`).
Load skill `flowgenie-creation-rules`.
MARKDOWN)]
class FlowGenieServer extends Server
{
    /**
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        GetMcpGuidanceTool::class,
        GetMcpSkillTool::class,
        ListFlowGenieCategoriesTool::class,
        ListFlowGeniesTool::class,
        GetFlowGenieTool::class,
        ValidateFlowGenieTool::class,
        CreateFlowGenieTool::class,
        UpdateFlowGenieTool::class,
        DeleteFlowGenieTool::class,
    ];

    public function createContext(): ServerContext
    {
        app()->instance(McpGuidance::SKILL_SCOPE_KEY, McpGuidance::SCOPE_FLOW_GENIES);

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
