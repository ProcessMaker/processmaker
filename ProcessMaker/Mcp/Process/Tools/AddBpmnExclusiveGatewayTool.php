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

#[Description('Add an exclusiveGateway to a PM4 process BPMN.')]
class AddBpmnExclusiveGatewayTool extends Tool
{
    protected string $name = 'add_bpmn_exclusive_gateway';

    public function __construct(private readonly BpmnDesigner $designer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'name' => 'required|string',
            'element_id' => 'sometimes|string',
        ]);

        return Response::structured($this->designer->addExclusiveGateway(
            $data['process_id'],
            $data['name'],
            $data['element_id'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->required(),
            'name' => $schema->string()->description('Gateway name')->required(),
            'element_id' => $schema->string()->description('Optional element ID'),
        ];
    }
}
