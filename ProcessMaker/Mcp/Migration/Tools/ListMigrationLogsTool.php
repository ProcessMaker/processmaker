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

#[Description('List PM3→PM4 migration logs with status, process_id, and pending steps.')]
class ListMigrationLogsTool extends Tool
{
    protected string $name = 'list_migration_logs';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        return Response::structured([
            'logs' => $this->writer->listMigrationLogs(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
