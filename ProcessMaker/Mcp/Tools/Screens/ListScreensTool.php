<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Screens;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\Screens\ScreenCatalog;

#[Name('list-screens')]
#[Title('List Screens')]
#[Description('Lists ProcessMaker screens. Filter by title, description, or type. System screens are hidden unless include_system is true.')]
#[IsReadOnly]
#[IsIdempotent]
class ListScreensTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $screens->list(
            $screens->actor($request->user()),
            $request->all(),
        ));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()
                ->description('Case-insensitive match against title or description.'),
            'type' => ScreenFields::type($schema, false),
            'page' => $schema->integer()
                ->description('Page number, starting at 1.')
                ->default(1),
            'per_page' => $schema->integer()
                ->description('Page size. Maximum 50.')
                ->default(15),
            'order_by' => $schema->string()
                ->enum(['title', 'type', 'updated_at', 'created_at', 'id'])
                ->description('Column to sort by.')
                ->default('title'),
            'order_direction' => $schema->string()
                ->enum(['asc', 'desc'])
                ->description('Sort direction.')
                ->default('asc'),
            'include_system' => $schema->boolean()
                ->description('Include system screens.')
                ->default(false),
        ];
    }
}
