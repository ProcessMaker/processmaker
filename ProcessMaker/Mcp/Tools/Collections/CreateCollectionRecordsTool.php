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

#[Name('create-collection-records')]
#[Title('Create Collection Records')]
#[Description('Adds one or more test/demo records to a collection. Pass records (array of data objects) or data (single object). Load skill collection-test-data.')]
class CreateCollectionRecordsTool extends RespondingTool
{
    public function handle(Request $request, CollectionCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->createRecords(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'collection_id' => $schema->integer()->description('Collection id.')->required(),
            'records' => $schema->array()->items($schema->object())->description('Array of record data objects (field => value). Max 100.'),
            'data' => $schema->object()->description('Single record data object when seeding one row.'),
        ];
    }
}
