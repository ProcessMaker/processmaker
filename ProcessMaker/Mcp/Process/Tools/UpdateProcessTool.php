<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\Writer;

#[Description('Update PM4 process metadata (name, description, status, category).')]
class UpdateProcessTool extends Tool
{
    protected string $name = 'update_process';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED',
            'process_category_id' => 'sometimes|integer|exists:process_categories,id',
        ]);

        $processId = $data['process_id'];
        unset($data['process_id']);

        $process = $this->writer->updateProcess($processId, $data);

        return Response::structured([
            'id' => $process->id,
            'name' => $process->name,
            'status' => $process->status,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
            'name' => $schema->string()->description('Process name'),
            'description' => $schema->string()->description('Process description'),
            'status' => $schema->string()->description('ACTIVE, INACTIVE, or ARCHIVED'),
            'process_category_id' => $schema->integer()->description('Process category ID'),
        ];
    }
}
