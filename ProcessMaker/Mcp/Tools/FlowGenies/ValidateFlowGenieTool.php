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

#[Name('validate-flow-genie')]
#[Title('Validate FlowGenie Config')]
#[Description('Validates a FlowGenie config object without saving. Returns ok, errors, warnings, and normalized config when valid.')]
class ValidateFlowGenieTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->validateConfig(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'config' => FlowGenieFields::config($schema),
        ];
    }
}
