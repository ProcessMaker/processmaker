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
use ProcessMaker\Mcp\DataConnectors\DataConnectorCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('create-data-connector')]
#[Title('Create Data Connector')]
#[Description('Creates a REST/SOAP Data Connector. Requires name, type, authtype, data_source_category_id. Load skill data-connector-creation-rules first.')]
class CreateDataConnectorTool extends RespondingTool
{
    public function handle(Request $request, DataConnectorCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->create(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => DataConnectorFields::name($schema, true),
            'description' => DataConnectorFields::description($schema, false),
            'type' => DataConnectorFields::type($schema, true),
            'authtype' => DataConnectorFields::authtype($schema, true),
            'data_source_category_id' => DataConnectorFields::category($schema, true),
            'status' => $schema->string()->enum(['ACTIVE', 'INACTIVE'])->default('ACTIVE'),
            'api_base_url' => $schema->string()->description('Base URL for {{__api_base_url__}} in endpoint urls.'),
            'endpoints' => DataConnectorFields::endpoints($schema),
            'credentials' => DataConnectorFields::credentials($schema),
            'debug_mode' => $schema->boolean()->default(false),
        ];
    }
}
