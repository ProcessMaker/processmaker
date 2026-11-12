<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Tools\GetMcpGuidanceTool;
use ProcessMaker\Mcp\Tools\GetMcpSkillTool;
use ProcessMaker\Mcp\Tools\Processes\CreateProcessTool;
use ProcessMaker\Mcp\Tools\Processes\GetProcessTool;
use ProcessMaker\Mcp\Tools\Processes\ListGroupsTool;
use ProcessMaker\Mcp\Tools\Processes\ListProcessesTool;
use ProcessMaker\Mcp\Tools\Processes\ListUsersTool;
use ProcessMaker\Mcp\Tools\Processes\PlanProcessTool;
use ProcessMaker\Mcp\Tools\Processes\UpdateProcessTool;
use ProcessMaker\Mcp\Tools\Processes\ValidateProcessTool;

#[Name('ProcessMaker Processes')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Manage ProcessMaker processes (BPMN workflows).

Before any other ProcessMaker Processes tool:
1. Call `get-mcp-guidance` for the system prompt and skills catalog.
2. From the catalog, load only skills whose `when` matches the current task via `get-mcp-skill` (or the skill Resource URI).
3. Do not load every skill by default. Follow the system prompt and any skills you loaded.

## Designing a new process
When the request is vague or incomplete, call `plan-process` first.
Ask the human the returned `open_questions`. Use `list-users` and `list-groups` so they can pick real assignees / process manager.
Re-call `plan-process` with answers until `ready` is true, then build screens/BPMN.

Use list-processes to search before creating one.
create-process requires name and description; bpmn is optional (empty template if omitted — add a start event before running).
For MCP create-process/update-process use numeric pm:screenRef / pm:scriptRef (list-screens after create-screen). Portable ${screen:title} only resolves via package-plg deploy-process.
For BPMN structure, node settings, and package connectors (email, PDF, Slack, FlowGenie, Decision Task, UiPath, DocuSign, IDP, Actions By Email, Data Connector), follow the matching `pm4-*` skill from the ProcessMaker Skills repository (`npx skills add processmaker/skills`, start with `pm4-process`). Do not invent `implementation` strings.
For FlowGenie Service Tasks: create/configure the asset via FlowGenies MCP (`create-flow-genie`) first, then set numeric `flow_genie_id` in pm:config.
Call validate-process before create/update when unsure.
update-process changes only the fields you send.
MARKDOWN)]
class ProcessServer extends Server
{
    /**
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        GetMcpGuidanceTool::class,
        GetMcpSkillTool::class,
        PlanProcessTool::class,
        ListUsersTool::class,
        ListGroupsTool::class,
        ListProcessesTool::class,
        GetProcessTool::class,
        CreateProcessTool::class,
        UpdateProcessTool::class,
        ValidateProcessTool::class,
    ];

    public function createContext(): ServerContext
    {
        app()->instance(McpGuidance::SKILL_SCOPE_KEY, McpGuidance::SCOPE_PROCESSES);

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
