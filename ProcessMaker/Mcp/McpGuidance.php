<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp;

use Illuminate\Support\Str;
use ProcessMaker\Models\Setting;

class McpGuidance
{
    public const SYSTEM_PROMPT_KEY = 'mcp.system_prompt';

    public const SKILLS_KEY = 'mcp.skills';

    public const SKILL_SCOPE_KEY = 'mcp.skills.scope';

    public const SCOPE_SCREENS = 'screens';

    public const SCOPE_PROCESSES = 'processes';

    public const SCOPE_DATA_CONNECTORS = 'data-connectors';

    public function systemPrompt(): string
    {
        $value = Setting::configByKey(self::SYSTEM_PROMPT_KEY);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<array{id: string, name: string, slug: string, when: string, content: string, system: bool, scope: string}>
     */
    public function skills(?string $scope = null): array
    {
        $raw = Setting::configByKey(self::SKILLS_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $activeScope = $scope ?? $this->boundScope();
        $skills = [];
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = $this->normalizeSkill($item, $index);
            if ($normalized === null) {
                continue;
            }

            if (!$this->matchesScope($normalized['scope'], $activeScope)) {
                continue;
            }

            $skills[] = $normalized;
        }

        return $skills;
    }

    /**
     * @return array{id: string, name: string, slug: string, when: string, content: string, system: bool, scope: string}|null
     */
    public function skillBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        // Lookup ignores server scope so an agent can load a known slug on demand.
        foreach ($this->skillsWithoutScopeFilter() as $skill) {
            if ($skill['slug'] === $slug) {
                return $skill;
            }
        }

        return null;
    }

    public function instructionsAppendix(): string
    {
        $sections = [];

        $prompt = $this->systemPrompt();
        if ($prompt !== '') {
            $sections[] = "## System Prompt\n\n" . $prompt;
        }

        $skills = $this->skills();
        if ($skills !== []) {
            $lines = [
                '## Skills catalog',
                '',
                'Load only skills whose `when` matches the current task.',
                'Call `get-mcp-skill` with the skill slug (or read the Resource URI).',
                'Do not load every skill by default.',
                '',
            ];

            foreach ($skills as $skill) {
                $when = $skill['when'] !== '' ? $skill['when'] : 'Use when relevant to the task';
                $lines[] = "- **{$skill['name']}** — when: {$when} — slug: `{$skill['slug']}` — uri: `pm://mcp/skills/{$skill['slug']}`";
            }

            $sections[] = implode("\n", $lines);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Catalog only — full skill bodies are loaded on demand via get-mcp-skill / resources.
     *
     * @return array{system_prompt: string, skills: list<array{id: string, name: string, slug: string, when: string, uri: string}>}
     */
    public function payload(): array
    {
        return [
            'system_prompt' => $this->systemPrompt(),
            'skills' => array_map(
                static fn (array $skill): array => [
                    'id' => $skill['id'],
                    'name' => $skill['name'],
                    'slug' => $skill['slug'],
                    'when' => $skill['when'],
                    'uri' => 'pm://mcp/skills/' . $skill['slug'],
                ],
                $this->skills(),
            ),
        ];
    }

    /**
     * @return list<array{id: string, name: string, slug: string, when: string, content: string, system: bool, scope: string}>
     */
    private function skillsWithoutScopeFilter(): array
    {
        $raw = Setting::configByKey(self::SKILLS_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $skills = [];
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized = $this->normalizeSkill($item, $index);
            if ($normalized !== null) {
                $skills[] = $normalized;
            }
        }

        return $skills;
    }

    /**
     * @param array<string, mixed> $item
     * @return array{id: string, name: string, slug: string, when: string, content: string, system: bool, scope: string}|null
     */
    private function normalizeSkill(array $item, int $index): ?array
    {
        $name = trim((string) ($item['name'] ?? ''));
        $content = trim((string) ($item['content'] ?? ''));
        if ($name === '' && $content === '') {
            return null;
        }

        if ($name === '') {
            $name = 'Skill ' . ($index + 1);
        }

        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($name);
        }
        if ($slug === '') {
            $slug = 'skill-' . ($index + 1);
        }

        $id = trim((string) ($item['id'] ?? ''));
        if ($id === '') {
            $id = $slug;
        }

        $scope = trim((string) ($item['scope'] ?? ''));

        return [
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'when' => trim((string) ($item['when'] ?? '')),
            'content' => $content,
            'system' => !empty($item['system']),
            'scope' => $scope,
        ];
    }

    private function matchesScope(string $skillScope, ?string $activeScope): bool
    {
        if ($activeScope === null || $activeScope === '') {
            return true;
        }

        if ($skillScope === '' || $skillScope === 'all') {
            return true;
        }

        return $skillScope === $activeScope;
    }

    private function boundScope(): ?string
    {
        if (!app()->bound(self::SKILL_SCOPE_KEY)) {
            return null;
        }

        $scope = app(self::SKILL_SCOPE_KEY);

        return is_string($scope) ? $scope : null;
    }
}
