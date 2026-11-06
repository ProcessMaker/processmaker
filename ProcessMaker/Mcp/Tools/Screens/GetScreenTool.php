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

#[Name('get-screen')]
#[Title('Get Screen')]
#[Description('Returns one ProcessMaker screen, including its builder config.')]
#[IsReadOnly]
#[IsIdempotent]
class GetScreenTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(function () use ($request, $screens): array {
            $validated = $request->validate([
                'id' => 'required|integer|min:1',
            ]);

            return $screens->find($screens->actor($request->user()), (int) $validated['id']);
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
