<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class McpSkillResource extends Resource
{
    public function __construct(
        private readonly string $skillName,
        private readonly string $slug,
        private readonly string $content,
        string $when = '',
    ) {
        $this->name = 'mcp-skill-' . $slug;
        $this->title = $skillName;
        $this->description = $when !== ''
            ? 'MCP skill (when: ' . $when . ')'
            : 'MCP skill: ' . $skillName;
        $this->uri = 'pm://mcp/skills/' . $slug;
        $this->mimeType = 'text/markdown';
    }

    public function handle(Request $request): Response
    {
        return Response::text($this->content !== '' ? $this->content : '_No content._');
    }
}
