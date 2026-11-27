<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Processes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('list-processes')]
#[Title('List Processes')]
#[Description('Lists ProcessMaker processes. Filter by name/description or status. Templates are hidden unless include_templates is true.')]
#[IsReadOnly]
#[IsIdempotent]
class ListProcessesTool extends ProcessTool
{
    public function handle(Request $request, ProcessCatalog $processes): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $processes->list(
            $processes->actor($request->user()),
            $request->all(),
        ));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Search name or description.'),
            'status' => $schema->string()->description(ProcessFields::STATUS),
            'page' => $schema->integer()->description('Page number (default 1).'),
            'per_page' => $schema->integer()->description('Results per page (max 50).'),
            'order_by' => $schema->string()->description('Order column: name, status, updated_at, created_at, id.'),
            'order_direction' => $schema->string()->description('asc or desc.'),
            'include_templates' => $schema->boolean()->description('Include process templates (default false).'),
        ];
    }
}
