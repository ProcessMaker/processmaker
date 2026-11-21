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

#[Name('get-collection')]
#[Title('Get Collection')]
#[Description('Returns one collection including screen ids and columns.')]
#[IsReadOnly]
#[IsIdempotent]
class GetCollectionTool extends RespondingTool
{
    public function handle(Request $request, CollectionCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $catalog): array {
            $validated = $request->validate(['id' => 'required|integer|min:1']);

            return $catalog->find($catalog->actor($request->user()), (int) $validated['id']);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => CollectionFields::id($schema),
        ];
    }
}
