<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\DataConnectors;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\DataConnectors\DataConnectorCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('list-data-connectors')]
#[Title('List Data Connectors')]
#[Description('Lists ProcessMaker Data Connectors. Filter by name/description. Defaults to REST non-template connectors.')]
#[IsReadOnly]
#[IsIdempotent]
class ListDataConnectorsTool extends RespondingTool
{
    public function handle(Request $request, DataConnectorCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->list(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Case-insensitive match against name or description.'),
            'type' => $schema->string()->enum(['REST', 'SOAP'])->description('Connector type. Default REST.'),
            'status' => $schema->string()->enum(['ACTIVE', 'INACTIVE']),
            'page' => $schema->integer()->description('Page number, starting at 1.')->default(1),
            'per_page' => $schema->integer()->description('Page size. Maximum 50.')->default(15),
            'order_by' => $schema->string()->enum(['name', 'type', 'status', 'updated_at', 'created_at', 'id'])->default('name'),
            'order_direction' => $schema->string()->enum(['asc', 'desc'])->default('asc'),
        ];
    }
}
