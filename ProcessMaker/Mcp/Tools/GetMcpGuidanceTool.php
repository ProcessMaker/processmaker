<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use ProcessMaker\Mcp\McpGuidance;

#[Name('get-mcp-guidance')]
#[Title('Get MCP Guidance')]
#[Description('Returns the admin system prompt and skills catalog (name, when, slug, uri) without skill bodies. Call before other ProcessMaker MCP tools; then load matching skills with get-mcp-skill.')]
#[IsReadOnly]
#[IsIdempotent]
class GetMcpGuidanceTool extends Tool
{
    public function handle(Request $request, McpGuidance $guidance): Response|ResponseFactory
    {
        return Response::structured($guidance->payload());
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
