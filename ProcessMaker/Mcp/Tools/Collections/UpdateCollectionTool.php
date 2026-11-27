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
use ProcessMaker\Mcp\DataConnectors\CollectionCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('update-collection')]
#[Title('Update Collection')]
#[Description('Updates a collection. Send only the fields that should change.')]
#[IsIdempotent]
class UpdateCollectionTool extends RespondingTool
{
    public function handle(Request $request, CollectionCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->update(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => CollectionFields::id($schema),
            'name' => CollectionFields::name($schema, false),
            'description' => CollectionFields::description($schema, false),
            'create_screen_id' => CollectionFields::screenId($schema, 'Create', false),
            'read_screen_id' => CollectionFields::screenId($schema, 'Read/view', false),
            'update_screen_id' => CollectionFields::screenId($schema, 'Update', false),
            'custom_title' => $schema->string(),
            'obfuscate' => $schema->boolean(),
            'type' => $schema->string()->enum(['NORMAL', 'RAG']),
        ];
    }
}
