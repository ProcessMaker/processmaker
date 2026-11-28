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

#[Description('Resume an interrupted PM3→PM4 migration from the saved migration log. Skips completed steps and reuses imported artifacts.')]
class ResumeMigrationBundleTool extends Tool
{
    protected string $name = 'resume_migration_bundle';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'bundle' => 'required|array',
        ]);

        return Response::structured($this->writer->resumeMigrationBundle($data['bundle']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'bundle' => $schema->object()->description('Same normalized PM3 bundle used for the original import')->required(),
        ];
    }
}
