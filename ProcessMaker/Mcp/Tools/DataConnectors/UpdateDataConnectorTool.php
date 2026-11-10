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
use ProcessMaker\Mcp\DataConnectors\DataConnectorCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('update-data-connector')]
#[Title('Update Data Connector')]
#[Description('Updates a Data Connector. Send only fields that should change. Replacing endpoints replaces the entire map.')]
#[IsIdempotent]
class UpdateDataConnectorTool extends RespondingTool
{
    public function handle(Request $request, DataConnectorCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->update(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => DataConnectorFields::id($schema),
            'name' => DataConnectorFields::name($schema, false),
            'description' => DataConnectorFields::description($schema, false),
            'type' => DataConnectorFields::type($schema, false),
            'authtype' => DataConnectorFields::authtype($schema, false),
            'data_source_category_id' => DataConnectorFields::category($schema, false),
            'status' => $schema->string()->enum(['ACTIVE', 'INACTIVE']),
            'api_base_url' => $schema->string()->description('Base URL for {{__api_base_url__}}.'),
            'endpoints' => DataConnectorFields::endpoints($schema),
            'credentials' => DataConnectorFields::credentials($schema),
            'debug_mode' => $schema->boolean(),
        ];
    }
}
