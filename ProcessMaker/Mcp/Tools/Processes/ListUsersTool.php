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
use ProcessMaker\Mcp\Processes\ProcessCatalog;
use ProcessMaker\Mcp\Processes\UserCatalog;

#[Name('list-users')]
#[Title('List Users')]
#[Description('Lists ProcessMaker users for process assignment (process_manager, user ids). Filter by username, name, or email. Defaults to ACTIVE non-system users. Use while planning or wiring Form Task assignments.')]
#[IsReadOnly]
#[IsIdempotent]
class ListUsersTool extends ProcessTool
{
    public function handle(
        Request $request,
        ProcessCatalog $processes,
        UserCatalog $users,
    ): Response|ResponseFactory {
        return $this->respond(fn (): array => $users->list(
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
            'filter' => $schema->string()->description('Search username, firstname, lastname, or email.'),
            'status' => $schema->string()->description('ACTIVE or INACTIVE (default ACTIVE).'),
            'page' => $schema->integer()->description('Page number (default 1).'),
            'per_page' => $schema->integer()->description('Results per page (max 50).'),
            'order_by' => $schema->string()->description('Order column: id, username, firstname, lastname, email, status.'),
            'order_direction' => $schema->string()->description('asc or desc.'),
        ];
    }
}
