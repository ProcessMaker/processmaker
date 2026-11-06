<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools\Processes;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\Processes\GroupCatalog;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('list-groups')]
#[Title('List Groups')]
#[Description('Lists ProcessMaker groups for Form Task user_group assignment (pm:assignedGroups). Filter by name or description. Defaults to ACTIVE groups. Use while planning process assignees.')]
#[IsReadOnly]
#[IsIdempotent]
class ListGroupsTool extends ProcessTool
{
    public function handle(
        Request $request,
        ProcessCatalog $processes,
        GroupCatalog $groups,
    ): Response|ResponseFactory {
        return $this->respond(fn (): array => $groups->list(
            $processes->actor($request->user()),
            $request->all(),
        ));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->description('Search name or description.'),
            'status' => $schema->string()->description('ACTIVE or INACTIVE (default ACTIVE).'),
            'page' => $schema->integer()->description('Page number (default 1).'),
            'per_page' => $schema->integer()->description('Results per page (max 50).'),
            'order_by' => $schema->string()->description('Order column: id, name, status, updated_at, created_at.'),
            'order_direction' => $schema->string()->description('asc or desc.'),
        ];
    }
}
