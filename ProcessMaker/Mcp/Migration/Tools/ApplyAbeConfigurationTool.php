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

#[Description('Apply PM3 Actions By Email configuration to PM4 BPMN task elements.')]
class ApplyAbeConfigurationTool extends Tool
{
    protected string $name = 'apply_abe_configuration';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'abe_configurations' => 'required|array',
            'screens' => 'nullable|array',
            'tasks' => 'nullable|array',
            'task_element_map' => 'nullable|array',
            'email_server_map' => 'nullable|array',
        ]);

        return Response::structured($this->writer->applyAbeConfiguration(
            $data['process_id'],
            $data
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
            'abe_configurations' => $schema->array()->description('Normalized PM3 ABE configurations')->required(),
            'screens' => $schema->array()->description('Screen mappings from migrated dynaforms'),
            'tasks' => $schema->array()->description('PM3 normalized tasks'),
            'task_element_map' => $schema->array()->description('tas_uid to BPMN element_id map'),
            'email_server_map' => $schema->array()->description('Optional PM3 MESS_UID to PM4 email server ID map'),
        ];
    }
}
