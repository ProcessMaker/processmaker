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

#[Name('delete-flow-genie')]
#[Title('Delete FlowGenie')]
#[Description('Deletes a FlowGenie asset by id. Processes that reference flow_genie_id will fail at runtime until updated.')]
class DeleteFlowGenieTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->delete(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => FlowGenieFields::id($schema),
        ];
    }
}
