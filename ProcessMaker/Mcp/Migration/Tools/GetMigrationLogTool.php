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

#[Description('Return the migration log for a PM3 pro_uid: imported artifacts, completed steps, pending steps, and errors.')]
class GetMigrationLogTool extends Tool
{
    protected string $name = 'get_migration_log';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'pro_uid' => 'required|string',
        ]);

        return Response::structured($this->writer->getMigrationLog($data['pro_uid']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'pro_uid' => $schema->string()->description('PM3 process UID used as migration log key')->required(),
        ];
    }
}
