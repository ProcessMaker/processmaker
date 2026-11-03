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

#[Description('Apply process variables to a PM4 process (properties.variables + request_data_defaults).')]
class ApplyProcessVariablesTool extends Tool
{
    protected string $name = 'apply_process_variables';

    public function __construct(private readonly Designer $designer)
    {
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'process_id' => 'required|string|exists:processes,id',
            'variables' => 'required|array',
        ]);

        return Response::structured($this->designer->applyVariables(
            $data['process_id'],
            $data['variables'],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'process_id' => $schema->string()->required(),
            'variables' => $schema->array()->description('[{ var_name, var_default, var_label }]')->required(),
        ];
    }
}
