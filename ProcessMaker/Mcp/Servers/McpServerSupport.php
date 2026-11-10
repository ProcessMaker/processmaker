<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Servers;

use ProcessMaker\Mcp\McpGuidance;
use ProcessMaker\Mcp\Resources\McpSkillResource;

final class McpServerSupport
{
    /**
     * @return list<McpSkillResource>
     */
    public static function skillResources(McpGuidance $guidance): array
    {
        $resources = [];
        foreach ($guidance->skills() as $skill) {
            $resources[] = new McpSkillResource(
                $skill['name'],
                $skill['slug'],
                $skill['content'],
                $skill['when'],
            );
        }

        return $resources;
    }
}
