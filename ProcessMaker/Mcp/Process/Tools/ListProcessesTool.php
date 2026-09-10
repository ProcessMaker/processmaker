<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Process\Reader;

#[Description('List ProcessMaker 4 processes with optional name filter.')]
class ListProcessesTool extends Tool
{
    protected string $name = 'list_processes';

    public function __construct(private readonly Reader $reader)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'start' => 'sometimes|integer|min:0',
            'limit' => 'sometimes|integer|min:1|max:100',
            'filter_name' => 'sometimes|string',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,ARCHIVED,all',
        ]);

        return Response::structured($this->reader->listProcesses(
            (int) ($data['start'] ?? 0),
            (int) ($data['limit'] ?? 25),
            $data['filter_name'] ?? null,
            $data['status'] ?? 'ACTIVE',
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start' => $schema->integer()->description('Pagination offset'),
            'limit' => $schema->integer()->description('Max results (default 25)'),
            'filter_name' => $schema->string()->description('Filter by process name'),
            'status' => $schema->string()->description('ACTIVE, INACTIVE, ARCHIVED, or all'),
        ];
    }
}
