<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Process\Reader;

#[Description('List PM4 scripts.')]
class ListScriptsTool extends Tool
{
    protected string $name = 'list_scripts';

    public function __construct(private readonly Reader $reader)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'start' => 'sometimes|integer|min:0',
            'limit' => 'sometimes|integer|min:1|max:100',
            'filter' => 'sometimes|string',
        ]);

        return Response::structured($this->reader->listScripts(
            (int) ($data['start'] ?? 0),
            (int) ($data['limit'] ?? 25),
            $data['filter'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start' => $schema->integer(),
            'limit' => $schema->integer(),
            'filter' => $schema->string(),
        ];
    }
}
