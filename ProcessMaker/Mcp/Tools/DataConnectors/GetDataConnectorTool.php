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

#[Name('get-data-connector')]
#[Title('Get Data Connector')]
#[Description('Returns one Data Connector including endpoints. Credentials are omitted.')]
#[IsReadOnly]
#[IsIdempotent]
class GetDataConnectorTool extends RespondingTool
{
    public function handle(Request $request, DataConnectorCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $catalog): array {
            $validated = $request->validate(['id' => 'required|integer|min:1']);

            return $catalog->find($catalog->actor($request->user()), (int) $validated['id']);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => DataConnectorFields::id($schema),
        ];
    }
}
