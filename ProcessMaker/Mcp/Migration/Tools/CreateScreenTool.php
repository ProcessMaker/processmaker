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

#[Description('Create a new screen in ProcessMaker 4.')]
class CreateScreenTool extends Tool
{
    protected string $name = 'create_screen';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'config_json' => 'required',
        ]);

        $result = $this->writer->createScreenResult($data);

        return Response::structured($this->writer->screenCreationPayload($result));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Screen title')->required(),
            'description' => $schema->string()->description('Screen description'),
            'type' => $schema->string()->description('Screen type, e.g. FORM'),
            'config_json' => $schema->anyOf([
                $schema->string()->description('Screen config JSON string'),
                $schema->object()->description('Screen config object'),
            ])->required(),
        ];
    }
}
