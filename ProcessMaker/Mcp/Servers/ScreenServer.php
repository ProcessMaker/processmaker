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
use ProcessMaker\Mcp\Tools\Screens\CreateScreenTool;
use ProcessMaker\Mcp\Tools\Screens\DeleteScreenTool;
use ProcessMaker\Mcp\Tools\Screens\GetScreenTool;
use ProcessMaker\Mcp\Tools\Screens\ListScreensTool;
use ProcessMaker\Mcp\Tools\Screens\UpdateScreenTool;
use ProcessMaker\Mcp\Tools\Screens\ValidateScreenTool;

#[Name('ProcessMaker Screens')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Manage ProcessMaker screens.

Before any other ProcessMaker Screens tool:
1. Call `get-mcp-guidance` for the system prompt and skills catalog.
2. From the catalog, load only skills whose `when` matches the current task via `get-mcp-skill` (or the skill Resource URI).
3. Do not load every skill by default. Follow the system prompt and any skills you loaded.

Use list-screens to search existing screens before creating one.
Call validate-screen before create-screen / update-screen when unsure (FormSelectList options, Vue component wiring).
create-screen requires title, description, type, and screen_category_id.
type is usually FORM or DISPLAY.
config is the screen builder page array; every control in page.items must include `"inspector": []` (or a real inspector list) or the designer crashes.
create/update reject configs with empty FormSelectList options (missing optionsList).
New FORM/DISPLAY screens are built as a Vue Component Editor mount (VueComponentRenderTemplate with a thin contentVue); do not add native FileUpload / FormSelectList / SignaturePad siblings.
For the full rules (control shape, component registration, domain fields, refactor parity), follow the `pm4-screen` skill from the ProcessMaker Skills repository (`npx skills add processmaker/skills`).
update-screen changes only the fields you send.
delete-screen cannot remove a default screen.
MARKDOWN)]
class ScreenServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        GetMcpGuidanceTool::class,
        GetMcpSkillTool::class,
        ListScreensTool::class,
        GetScreenTool::class,
        ValidateScreenTool::class,
        CreateScreenTool::class,
        UpdateScreenTool::class,
        DeleteScreenTool::class,
    ];

    public function createContext(): ServerContext
    {
        app()->instance(McpGuidance::SKILL_SCOPE_KEY, McpGuidance::SCOPE_SCREENS);

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
