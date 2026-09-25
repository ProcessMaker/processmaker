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

#[Description('Get a PM4 script by ID including code.')]
class GetScriptTool extends Tool
{
    protected string $name = 'get_script';

    public function __construct(private readonly Reader $reader)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'script_id' => 'required|string|exists:scripts,id',
        ]);

        return Response::structured($this->reader->getScript($data['script_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'script_id' => $schema->string()->required(),
        ];
    }
}
