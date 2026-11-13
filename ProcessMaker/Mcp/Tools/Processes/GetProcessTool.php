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

#[Name('get-process')]
#[Title('Get Process')]
#[Description('Returns one ProcessMaker process, including its BPMN XML.')]
#[IsReadOnly]
#[IsIdempotent]
class GetProcessTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $processes): array {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            return $processes->find(
                $processes->actor($request->user()),
                (int) $validated['id'],
            );
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => ProcessFields::id($schema),
        ];
    }
}
