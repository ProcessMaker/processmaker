<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\MigrationGapReport;

#[Description('Compare a PM3 bundle with a PM4 migration result and return remaining gaps.')]
class GetMigrationGapReportTool extends Tool
{
    protected string $name = 'get_migration_gap_report';

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'bundle' => 'required|array',
            'result' => 'required|array',
        ]);

        return Response::structured((new MigrationGapReport())->build($data['bundle'], $data['result']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'bundle' => $schema->object()->description('Normalized PM3 bundle')->required(),
            'result' => $schema->object()->description('Result returned by apply_migration_bundle')->required(),
        ];
    }
}
