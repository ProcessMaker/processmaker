<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Process\BpmnDesigner;

#[Description('Add a userTask to a PM4 process BPMN.')]
class AddBpmnUserTaskTool extends Tool
{
    protected string $name = 'add_bpmn_user_task';

    public function __construct(private readonly BpmnDesigner $designer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'name' => 'required|string',
            'element_id' => 'sometimes|string',
            'insert_after_element_id' => 'sometimes|string',
        ]);

        return Response::structured($this->designer->addUserTask(
            $data['process_id'],
            $data['name'],
            $data['element_id'] ?? null,
            $data['insert_after_element_id'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
            'name' => $schema->string()->description('Task name')->required(),
            'element_id' => $schema->string()->description('Optional BPMN element ID'),
            'insert_after_element_id' => $schema->string()->description('Insert after this element outgoing flow'),
        ];
    }
}
