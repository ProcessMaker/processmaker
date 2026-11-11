<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Processes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('create-process')]
#[Title('Create Process')]
#[Description('Creates a ProcessMaker process. Requires name and description. Optional bpmn (full XML); if omitted, an empty executable template is used — add a start event before running.')]
class CreateProcessTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $processes->create(
            $processes->actor($request->user()),
            $request->all(),
        ));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => ProcessFields::name($schema, true),
            'description' => ProcessFields::description($schema, true),
            'status' => ProcessFields::status($schema),
            'process_category_id' => ProcessFields::category($schema, false),
            'bpmn' => ProcessFields::bpmn($schema, false),
            'auto_layout_bpmn' => ProcessFields::autoLayoutBpmn($schema),
        ];
    }
}
