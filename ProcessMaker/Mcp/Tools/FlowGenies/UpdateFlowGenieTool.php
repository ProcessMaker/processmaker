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

#[Name('update-flow-genie')]
#[Title('Update FlowGenie')]
#[Description('Updates a FlowGenie. Send only fields to change. Sending config replaces the full agent config (normalized).')]
class UpdateFlowGenieTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->update(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => FlowGenieFields::id($schema),
            'name' => FlowGenieFields::name($schema, false),
            'description' => FlowGenieFields::description($schema, false),
            'flow_genie_category_id' => FlowGenieFields::category($schema, false),
            'config' => FlowGenieFields::config($schema),
        ];
    }
}
