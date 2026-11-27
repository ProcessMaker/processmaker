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

#[Name('list-data-connector-categories')]
#[Title('List Data Connector Categories')]
#[Description('Lists active Data Connector categories for create-data-connector.')]
#[IsReadOnly]
#[IsIdempotent]
class ListDataConnectorCategoriesTool extends RespondingTool
{
    public function handle(Request $request, DataConnectorCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->listCategories(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Case-insensitive match against category name.'),
            'page' => $schema->integer()->default(1),
            'per_page' => $schema->integer()->default(15),
        ];
    }
}
