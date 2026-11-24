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

#[Name('get-process-inventory')]
#[Title('Get Process Inventory')]
#[Description('Maps one process without returning its BPMN: activities with their screen and script (numeric id and title), screens with control count and field names, scripts with language and executor, gateways with the variables their conditions read, and which screens write each variable. Use it first for a health review or refactor instead of reading every asset. Scripts, data connectors and call activities can also set variables; their output is not analysed.')]
#[IsReadOnly]
#[IsIdempotent]
class GetProcessInventoryTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $processes): array {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
                'alternative' => 'nullable|in:A,B',
            ]);

            return $processes->inventory(
                $processes->actor($request->user()),
                (int) $validated['id'],
                $validated['alternative'] ?? 'A',
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
            'alternative' => ProcessFields::alternative($schema),
        ];
    }
}
