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
use ProcessMaker\Mcp\Processes\GroupCatalog;
use ProcessMaker\Mcp\Processes\ProcessCatalog;

#[Name('create-group')]
#[Title('Create Group')]
#[Description('Creates a user group (requires create-groups). The name must be unique and may contain only alphanumeric characters and spaces. Call list-groups with `name` first: if a group with that name exists, use its id instead of creating a second one. Add members in the admin UI or the groups API.')]
class CreateGroupTool extends ProcessTool
{
    public function handle(
        Request $request,
        ProcessCatalog $processes,
        GroupCatalog $groups,
    ): Response|ResponseFactory {
        return $this->respond(fn (): array => $groups->create(
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
            'name' => $schema->string()->description('Unique group name.')->required(),
            'description' => $schema->string()->description('Short description (max 255).'),
            'status' => $schema->string()->description('ACTIVE (default) or INACTIVE.'),
            'manager_id' => $schema->integer()->description('User id of the group manager.'),
        ];
    }
}
