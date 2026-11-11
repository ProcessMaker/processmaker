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
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('layout-bpmn')]
#[Title('Layout BPMN')]
#[Description('Applies bpmn-auto-layout-extended to BPMN diagram interchange (lanes, collaborations, shapes, edges). Pass bpmn XML and/or process id. Returns layouted bpmn, warnings, and layout_engine. Does not save. Prefer before validate-process / create-process when the agent authored structure only.')]
#[IsIdempotent]
class LayoutBpmnTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $processes->layoutBpmn(
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
            'bpmn' => ProcessFields::bpmn($schema, false),
            'id' => $schema->integer()->description('Existing process id whose BPMN should be layouted when bpmn is omitted.'),
        ];
    }
}
