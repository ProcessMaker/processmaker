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

#[Name('enable-process-alternative-b')]
#[Title('Enable process Alternative B')]
#[Description('Creates Alternative B for a process by cloning the current Alternative A draft/published BPMN (package-ab-testing). Idempotent if B already exists.')]
class EnableProcessAlternativeBTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $processes): array {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            return $processes->enableAlternativeB(
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
