<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\Process\Designer;

#[Description('Create a PM4 process from a structured design plan (process, screens, scripts, variables, tasks).')]
class ApplyProcessDesignTool extends Tool
{
    protected string $name = 'apply_process_design';

    public function __construct(private readonly Designer $designer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'plan' => 'required|array',
        ]);

        return Response::structured($this->designer->applyDesignPlan($data['plan']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'plan' => $schema->object()->description('Design plan: process, bpmn_template, screens, scripts, variables, tasks')->required(),
        ];
    }
}
