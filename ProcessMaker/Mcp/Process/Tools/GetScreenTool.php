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

#[Description('Get a PM4 screen by ID including config.')]
class GetScreenTool extends Tool
{
    protected string $name = 'get_screen';

    public function __construct(private readonly Reader $reader)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'screen_id' => 'required|string|exists:screens,id',
        ]);

        return Response::structured($this->reader->getScreen($data['screen_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'screen_id' => $schema->string()->required(),
        ];
    }
}
