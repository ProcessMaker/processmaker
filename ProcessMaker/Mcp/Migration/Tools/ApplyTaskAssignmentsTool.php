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

#[Description('Apply PM3 task assignment rules to PM4 BPMN elements.')]
class ApplyTaskAssignmentsTool extends Tool
{
    protected string $name = 'apply_task_assignments';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'task_assignments' => 'required|array',
            'tasks' => 'nullable|array',
            'task_element_map' => 'nullable|array',
            'identity_map' => 'nullable|array',
        ]);

        return Response::structured($this->writer->applyTaskAssignments(
            $data['process_id'],
            $data
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
            'task_assignments' => $schema->array()->description('Normalized PM3 task assignments')->required(),
            'tasks' => $schema->array()->description('PM3 normalized tasks'),
            'task_element_map' => $schema->array()->description('tas_uid to BPMN element_id map'),
            'identity_map' => $schema->object()->description('Optional; omit for default migration. Task assignment is configured manually in PM4 after import.'),
        ];
    }
}
