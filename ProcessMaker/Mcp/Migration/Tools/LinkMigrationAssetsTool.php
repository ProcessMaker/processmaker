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

#[Description('Auto-link migrated screens and scripts to BPMN elements using PM3 task steps.')]
class LinkMigrationAssetsTool extends Tool
{
    protected string $name = 'link_migration_assets';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'screens' => 'nullable|array',
            'scripts' => 'nullable|array',
            'steps' => 'nullable|array',
            'tasks' => 'nullable|array',
            'script_tasks' => 'nullable|array',
            'task_element_map' => 'nullable|array',
        ]);

        return Response::structured($this->writer->autoLinkMigrationAssets(
            $data['process_id'],
            $data
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('PM4 process ID')->required(),
            'screens' => $schema->array()->description('Screen mappings from apply_migration_bundle'),
            'scripts' => $schema->array()->description('Script mappings from apply_migration_bundle'),
            'steps' => $schema->array()->description('PM3 normalized steps'),
            'tasks' => $schema->array()->description('PM3 normalized tasks'),
            'script_tasks' => $schema->array()->description('PM3 script task relations'),
            'task_element_map' => $schema->array()->description('tas_uid to BPMN element_id map'),
        ];
    }
}
