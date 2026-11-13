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
use ProcessMaker\Mcp\Screens\ScreenCatalog;

#[Name('create-screen')]
#[Title('Create Screen')]
#[Description('Creates a ProcessMaker screen. Requires title, description, type, and screen_category_id. Rejects invalid config (e.g. FormSelectList without optionsList). Prefer validate-screen first. Always include preview_presets covering empty/start, happy path, and each major UI branch; each preset.data must be a JSON string.')]
class CreateScreenTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $screens->create(
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
            'title' => ScreenFields::title($schema, true),
            'description' => ScreenFields::description($schema, true),
            'type' => ScreenFields::type($schema, true),
            'screen_category_id' => ScreenFields::category($schema, true),
            'config' => ScreenFields::config($schema),
            'computed' => $schema->array()->description('Computed properties for the screen.'),
            'watchers' => $schema->array()->description('Screen watchers.'),
            'preview_presets' => ScreenFields::previewPresets($schema),
            'custom_css' => $schema->string()->description('Custom CSS applied when the screen renders.'),
        ];
    }
}
