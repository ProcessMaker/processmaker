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

#[Description('Create a new process in ProcessMaker 4.')]
class CreateProcessTool extends Tool
{
    protected string $name = 'create_process';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:ACTIVE,INACTIVE,ARCHIVED',
            'bpmn' => 'nullable|string',
            'process_category_id' => 'nullable|integer|exists:process_categories,id',
        ]);

        $process = $this->writer->createProcess($data);

        return Response::structured([
            'id' => $process->id,
            'name' => $process->name,
            'status' => $process->status,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Process name')->required(),
            'description' => $schema->string()->description('Process description'),
            'status' => $schema->string()->description('ACTIVE, INACTIVE, or ARCHIVED'),
            'bpmn' => $schema->string()->description('BPMN XML definition'),
            'process_category_id' => $schema->integer()->description('Process category ID'),
        ];
    }
}
