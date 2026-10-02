<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\Writer;

#[Description('Create a new script in ProcessMaker 4.')]
class CreateScriptTool extends Tool
{
    protected string $name = 'create_script';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'code' => 'required|string',
            'language' => 'nullable|string',
            'run_as_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $script = $this->writer->createScript($data);

        return Response::structured([
            'id' => $script->id,
            'title' => $script->title,
            'language' => $script->language,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Script title')->required(),
            'description' => $schema->string()->description('Script description'),
            'code' => $schema->string()->description('Script source code')->required(),
            'language' => $schema->string()->description('Script language, e.g. php'),
            'run_as_user_id' => $schema->integer()->description('User ID to run as'),
        ];
    }
}
