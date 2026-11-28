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

#[Description('Validate a process BPMN definition in ProcessMaker 4.')]
class ValidateProcessTool extends Tool
{
    protected string $name = 'validate_process';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
        ]);

        return Response::structured($this->writer->validateProcess($data['process_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('Process ID')->required(),
        ];
    }
}
