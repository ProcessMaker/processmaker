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

#[Description('Create PM4 assets from a normalized PM3 migration bundle.')]
class ApplyMigrationBundleTool extends Tool
{
    protected string $name = 'apply_migration_bundle';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'bundle' => 'required|array',
            'resume' => 'sometimes|boolean',
            'fresh' => 'sometimes|boolean',
        ]);

        return Response::structured($this->writer->applyMigrationBundle(
            $data['bundle'],
            (bool) ($data['resume'] ?? false),
            (bool) ($data['fresh'] ?? false),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'bundle' => $schema->object()->description('Normalized PM3 bundle JSON')->required(),
            'resume' => $schema->boolean()->description('Continue from an interrupted migration log'),
            'fresh' => $schema->boolean()->description('Delete any existing log and start over'),
        ];
    }
}
