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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('validate-process')]
#[Title('Validate Process')]
#[Description('Validates BPMN without saving. Pass bpmn XML and/or an existing process id. Returns ok, errors, and warnings (schema + semantic checks). Load process-validation skill for the full checklist.')]
#[IsReadOnly]
#[IsIdempotent]
class ValidateProcessTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $processes->validate(
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
            'id' => $schema->integer()->description('Existing process id whose BPMN should be validated when bpmn is omitted.'),
        ];
    }
}
