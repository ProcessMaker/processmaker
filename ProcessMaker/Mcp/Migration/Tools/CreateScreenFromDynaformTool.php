<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\Writer;

#[Description('Create a PM4 screen from a PM3 dynaform definition.')]
class CreateScreenFromDynaformTool extends Tool
{
    protected string $name = 'create_screen_from_dynaform';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'dynaform' => 'required|array',
            'stylesheets' => 'nullable|array',
        ]);

        $result = $this->writer->createScreenFromDynaform(
            $data['dynaform'],
            $data['stylesheets'] ?? []
        );

        return Response::structured([
            'id' => $result['screen']->id,
            'title' => $result['screen']->title,
            'type' => $result['screen']->type,
            'warnings' => $result['warnings'],
            'nested_screens' => $result['nested_screens'],
            'custom_css' => $result['screen']->custom_css,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'dynaform' => $schema->object()->description('PM3 dynaform row with DYN_CONTENT')->required(),
            'stylesheets' => $schema->array()->description('Optional PM3 stylesheet assets for custom_css'),
        ];
    }
}
