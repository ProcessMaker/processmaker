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

#[Description('Get the BPMN XML of a PM4 process.')]
class GetProcessBpmnTool extends Tool
{
    protected string $name = 'get_process_bpmn';

    public function __construct(private readonly Reader $reader)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
        ]);

        return Response::structured($this->reader->getProcessBpmn($data['process_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
        ];
    }
}
