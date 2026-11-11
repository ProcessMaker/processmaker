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

#[Name('get-flow-genie')]
#[Title('Get FlowGenie')]
#[Description('Returns one FlowGenie including config (secrets redacted).')]
class GetFlowGenieTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->find(
            $catalog->actor($request->user()),
            (int) $request->get('id'),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => FlowGenieFields::id($schema),
        ];
    }
}
