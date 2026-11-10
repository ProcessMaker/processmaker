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

#[Name('validate-screen')]
#[Title('Validate Screen')]
#[Description('Validates a Screen Builder config without saving. Pass config and optional type, and/or an existing screen id. Returns ok, errors, and warnings (select options, Vue component wiring). Load screen-creation-rules for the checklist.')]
#[IsReadOnly]
#[IsIdempotent]
class ValidateScreenTool extends ScreenTool
{
    public function handle(Request $request, ScreenCatalog $screens): Response|ResponseFactory
    {
        return $this->respond(fn (): array => $screens->validate(
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
            'config' => ScreenFields::config($schema),
            'type' => ScreenFields::type($schema, false),
            'id' => $schema->integer()->description('Existing screen id whose config should be validated when config is omitted.'),
        ];
    }
}
