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

#[Description('Link a screen to a BPMN task element.')]
class LinkScreenToTaskTool extends Tool
{
    protected string $name = 'link_screen_to_task';

    public function __construct(private readonly Writer $writer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'element_id' => 'required|string',
            'screen_id' => 'required|string|exists:screens,id',
        ]);

        $process = $this->writer->linkScreenToTask(
            $data['process_id'],
            $data['element_id'],
            $data['screen_id']
        );

        return Response::structured([
            'process_id' => $process->id,
            'element_id' => $data['element_id'],
            'screen_id' => $data['screen_id'],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->description('Process ID')->required(),
            'element_id' => $schema->string()->description('BPMN element ID')->required(),
            'screen_id' => $schema->string()->description('Screen ID')->required(),
        ];
    }
}
