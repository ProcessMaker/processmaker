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
use ProcessMaker\Mcp\Screens\ScreenCatalog;

#[Name('update-screen')]
#[Title('Update Screen')]
#[Description('Updates a ProcessMaker screen. Send only the fields that should change. Rejects invalid config (e.g. FormSelectList without optionsList). Prefer validate-screen first. Does not reset config to the last published version. On create/refactor follow-ups, set preview_presets (each data as JSON string) covering all major UI cases.')]
#[IsIdempotent]
class UpdateScreenTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $screens->update(
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
            'id' => ScreenFields::id($schema),
            'title' => ScreenFields::title($schema, false),
            'description' => ScreenFields::description($schema, false),
            'type' => ScreenFields::type($schema, false),
            'screen_category_id' => ScreenFields::category($schema, false),
            'config' => ScreenFields::config($schema),
            'computed' => $schema->array()->description('Computed properties for the screen.'),
            'watchers' => $schema->array()->description('Screen watchers.'),
            'preview_presets' => ScreenFields::previewPresets($schema),
            'custom_css' => $schema->string()->description('Custom CSS applied when the screen renders.'),
        ];
    }
}
