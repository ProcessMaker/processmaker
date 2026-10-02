<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Mcp\Process\ScreenBuilder;

#[Description('Create a PM4 screen from a simple field list and optionally link to a BPMN task.')]
class CreateScreenFromFieldsTool extends Tool
{
    protected string $name = 'create_screen_from_fields';

    public function __construct(
        private readonly Writer $writer,
        private readonly ScreenBuilder $screenBuilder,
    ) {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'sometimes|string',
            'fields' => 'required|array',
            'process_id' => 'sometimes|string|exists:processes,id',
            'element_id' => 'sometimes|string',
        ]);

        $result = $this->writer->createScreenResult([
            'title' => $data['title'],
            'description' => $data['description'] ?? $data['title'],
            'config_json' => $this->screenBuilder->buildFormConfig($data['fields'], $data['title']),
        ]);

        $linked = null;
        if (!empty($data['process_id']) && !empty($data['element_id'])) {
            $this->writer->linkScreenToTask($data['process_id'], $data['element_id'], (string) $result->screen->id);
            $linked = ['process_id' => $data['process_id'], 'element_id' => $data['element_id']];
        }

        return Response::structured(array_merge(
            $this->writer->screenCreationPayload($result),
            ['linked' => $linked],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string(),
            'fields' => $schema->array()->description('[{ variable, label, type, required, default }]')->required(),
            'process_id' => $schema->string()->description('Optional process to link screen'),
            'element_id' => $schema->string()->description('BPMN task element ID for screenRef'),
        ];
    }
}
