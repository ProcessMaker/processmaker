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
use ProcessMaker\Mcp\DataConnectors\CollectionCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('create-collection')]
#[Title('Create Collection')]
#[Description('Creates a collection. Requires name, description, and create/read/update screen ids. May auto-create a Data Connector for records. See the pm4-collection skill.')]
class CreateCollectionTool extends RespondingTool
{
    public function handle(Request $request, CollectionCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->create(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => CollectionFields::name($schema, true),
            'description' => CollectionFields::description($schema, true),
            'create_screen_id' => CollectionFields::screenId($schema, 'Create', true),
            'read_screen_id' => CollectionFields::screenId($schema, 'Read/view', true),
            'update_screen_id' => CollectionFields::screenId($schema, 'Update', true),
            'custom_title' => $schema->string()->description('Optional Mustache title template for records.'),
            'obfuscate' => $schema->boolean()->default(false),
            'type' => $schema->string()->enum(['NORMAL', 'RAG'])->default('NORMAL'),
        ];
    }
}
