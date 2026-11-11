<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\FlowGenies;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use ProcessMaker\Mcp\FlowGenies\FlowGenieCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('create-flow-genie')]
#[Title('Create FlowGenie')]
#[Description('Creates a FlowGenie asset. Requires name, description, flow_genie_category_id. Prefer validate-flow-genie then set config.instructions. Load skill flowgenie-creation-rules.')]
class CreateFlowGenieTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->create(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => FlowGenieFields::name($schema, true),
            'description' => FlowGenieFields::description($schema, true),
            'flow_genie_category_id' => FlowGenieFields::category($schema, true),
            'config' => FlowGenieFields::config($schema),
        ];
    }
}
