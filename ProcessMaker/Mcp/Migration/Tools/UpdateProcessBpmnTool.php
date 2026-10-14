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

#[Description('Update the BPMN XML of an existing process.')]
class UpdateProcessBpmnTool extends Tool
{
    protected string $name = 'update_process_bpmn';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'bpmn' => 'required|string',
        ]);

        $process = $this->writer->updateProcessBpmn($data['process_id'], $data['bpmn']);

        return Response::structured([
            'process_id' => $process->id,
            'name' => $process->name,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('Process ID')->required(),
            'bpmn' => $schema->string()->description('BPMN XML')->required(),
        ];
    }
}
