<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Collections;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\DataConnectors\CollectionCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('list-collections')]
#[Title('List Collections')]
#[Description('Lists ProcessMaker collections. Filter by name or description.')]
#[IsReadOnly]
#[IsIdempotent]
class ListCollectionsTool extends RespondingTool
{
    public function handle(Request $request, CollectionCatalog $catalog): Response|ResponseFactory
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
            'page' => $schema->integer()->default(1),
            'per_page' => $schema->integer()->default(15),
            'order_by' => $schema->string()->enum(['name', 'updated_at', 'created_at', 'id'])->default('name'),
            'order_direction' => $schema->string()->enum(['asc', 'desc'])->default('asc'),
        ];
    }
}
