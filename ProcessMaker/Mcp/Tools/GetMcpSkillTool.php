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

#[Name('get-mcp-skill')]
#[Title('Get MCP Skill')]
#[Description('Returns the full markdown body of one admin-configured MCP skill by slug. Use after get-mcp-guidance: load only skills whose `when` matches the current task.')]
#[IsReadOnly]
#[IsIdempotent]
class GetMcpSkillTool extends Tool
{
    public function handle(Request $request, McpGuidance $guidance): Response|ResponseFactory
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255',
        ]);

        $skill = $guidance->skillBySlug((string) $validated['slug']);
        if ($skill === null) {
            return Response::error('Skill not found for slug: ' . $validated['slug']);
        }

        return Response::structured([
            'id' => $skill['id'],
            'name' => $skill['name'],
            'slug' => $skill['slug'],
            'when' => $skill['when'],
            'uri' => 'pm://mcp/skills/' . $skill['slug'],
            'content' => $skill['content'],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('Skill slug from the get-mcp-guidance catalog')
                ->required(),
        ];
    }
}
