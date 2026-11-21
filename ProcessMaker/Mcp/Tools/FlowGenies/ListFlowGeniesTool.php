<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\FlowGenies;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use ProcessMaker\Mcp\FlowGenies\FlowGenieCatalog;
use ProcessMaker\Mcp\Tools\RespondingTool;

#[Name('list-flow-genies')]
#[Title('List FlowGenies')]
#[Description('Lists FlowGenie assets (id, name, model, provider). Use filter to search by name/description/category.')]
class ListFlowGeniesTool extends RespondingTool
{
    public function handle(Request $request, FlowGenieCatalog $catalog): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $catalog->list(
            $catalog->actor($request->user()),
            $request->all(),
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Search name, description, or category.'),
            'page' => $schema->integer()->min(1)->default(1),
            'per_page' => $schema->integer()->min(1)->max(50)->default(15),
            'order_by' => $schema->string()->enum(['name', 'updated_at', 'created_at', 'id'])->default('name'),
            'order_direction' => $schema->string()->enum(['asc', 'desc'])->default('asc'),
        ];
    }
}
