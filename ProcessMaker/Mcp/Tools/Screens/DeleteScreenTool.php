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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use ProcessMaker\Mcp\Screens\ScreenCatalog;

#[Name('delete-screen')]
#[Title('Delete Screen')]
#[Description('Deletes a ProcessMaker screen. Default screens cannot be deleted.')]
#[IsDestructive]
class DeleteScreenTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $screens): array {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            return $screens->delete($screens->actor($request->user()), (int) $validated['id']);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => ScreenFields::id($schema),
        ];
    }
}
