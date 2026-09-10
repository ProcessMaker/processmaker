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

#[Description('Add a sequenceFlow between two BPMN elements in a PM4 process.')]
class AddBpmnSequenceFlowTool extends Tool
{
    protected string $name = 'add_bpmn_sequence_flow';

    public function __construct(private readonly BpmnDesigner $designer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'source_ref' => 'required|string',
            'target_ref' => 'required|string',
            'flow_id' => 'sometimes|string',
            'condition' => 'sometimes|string',
        ]);

        return Response::structured($this->designer->addSequenceFlow(
            $data['process_id'],
            $data['source_ref'],
            $data['target_ref'],
            $data['flow_id'] ?? null,
            $data['condition'] ?? null,
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->required(),
            'source_ref' => $schema->string()->description('Source BPMN element ID')->required(),
            'target_ref' => $schema->string()->description('Target BPMN element ID')->required(),
            'flow_id' => $schema->string()->description('Optional flow ID'),
            'condition' => $schema->string()->description('Optional condition label/expression'),
        ];
    }
}
