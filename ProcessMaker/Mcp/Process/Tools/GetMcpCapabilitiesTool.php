<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ProcessMaker\Mcp\McpSupport;

#[Description('Report MCP version, supported features, and platform compatibility checks. Call when PM4 was upgraded or MCP behaves unexpectedly.')]
class GetMcpCapabilitiesTool extends Tool
{
    protected string $name = 'get_mcp_capabilities';

    public function handle(Request $request): Response|ResponseFactory
    {
        return Response::structured(McpSupport::report());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
