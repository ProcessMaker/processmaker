<?php

namespace ProcessMaker\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VibeAiService
{
    public static function publicConfig(): array
    {
        $provider = env('VIBE_AI_PROVIDER', 'openai');

        return [
            'provider' => $provider,
            'model' => env('VIBE_AI_MODEL', 'gpt-4o-mini'),
            'configured' => $provider === 'cursor'
                ? true
                : (bool) env('VIBE_AI_API_KEY'),
            'cursorSidecarUrl' => $provider === 'cursor'
                ? env('VIBE_CURSOR_AGENT_URL', 'http://127.0.0.1:4877')
                : null,
        ];
    }

    public static function chat(array $payload): array
    {
        $provider = env('VIBE_AI_PROVIDER', 'openai');
        $messages = $payload['messages'] ?? [];
        $context = $payload['context'] ?? [];

        if (empty($messages)) {
            throw new \InvalidArgumentException('messages are required');
        }

        $systemPrompt = self::buildSystemPrompt($context);
        $chatMessages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            array_map(static function ($message) {
                return [
                    'role' => ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user',
                    'content' => $message['text'] ?? $message['content'] ?? '',
                ];
            }, $messages)
        );

        if ($provider === 'cursor') {
            $raw = self::callCursorSidecar($chatMessages, $context);
        } else {
            $raw = self::callOpenAiCompatible($chatMessages);
        }

        $parsed = self::normalizeAgentResult(self::parseAgentResponse($raw));

        return array_merge($parsed, ['provider' => $provider]);
    }

    private static function buildSystemPrompt(array $context): string
    {
        $activeFile = $context['activeFile'] ?? '(none)';
        $editorMode = $context['editorMode'] ?? 'code';
        $previewError = $context['previewError'] ?? '(none)';
        $activeFileContent = $context['activeFileContent'] ?? '';
        $projectFiles = $context['projectFiles'] ?? [];
        $selectedScenarioFile = $context['selectedScenarioFile'] ?? 'tests/scenarios.yaml';

        $contentBlock = $activeFileContent !== ''
            ? "```\n{$activeFileContent}\n```"
            : '(empty)';

        $filesBlock = '(no files listed)';
        if (is_array($projectFiles) && count($projectFiles) > 0) {
            $filesBlock = implode("\n", array_map(static function ($path) {
                return '- ' . $path;
            }, $projectFiles));
        }

        return <<<PROMPT
You are the Vibe Screen Builder AI assistant for ProcessMaker screens built as Vue 2 single-file components.

Project layout:
- screens/*.vue — screen entry components (create new screens here)
- components/*.vue — reusable Vue components (create new components here)
- tests/*.yaml — YAML test scenario files for the preview test runner
- index.js — re-exports the main screen entry

You CAN:
- Create NEW files under components/, screens/, and tests/
- Edit EXISTING screens, components, and test scenario files
- Update index.js when the user wants a different entry screen
- Create test scenarios when the user asks for tests, QA scenarios, or acceptance criteria

Path rules (strict):
- New component: components/PascalCaseName.vue
- New screen: screens/PascalCaseName.vue
- New test file: tests/kebab-case-scenarios.yaml (e.g. tests/pos-scenarios.yaml)
- Edit default tests: tests/scenarios.yaml
- Optional entry update: index.js
- Do NOT use paths outside components/, screens/, tests/, or index.js

Vue rules:
- Output valid Vue 2 SFC syntax (template, script, style scoped when needed)
- Use Options API (export default { ... })
- Screens import project components with: import Foo from "../components/Foo.vue"
- Use clear props on reusable components
- Prefer simple, accessible markup
- Use data-cy attributes on interactive elements and key regions for test targeting
- When creating or editing, return the FULL file content for each changed file

Test scenario YAML rules:
- Top-level key: scenarios (array)
- Each scenario: name, optional description, optional given.data, when (actions), then (assertions)
- Actions: { action: fill|click, field|target: data-cy value, value?: string }
- Assertions: { assert: visible, target: data-cy } or { assert: data, field: vmField, equals: value }
- Target elements by data-cy values from the active screen/components
- Read the screen source to discover existing data-cy attributes before writing tests
- ALWAYS include the full YAML file in edits when creating or updating scenarios (never only describe the file in message)

Respond with JSON only (no markdown fences):
{
  "message": "Brief explanation for the user",
  "edits": [
    { "path": "components/MyComponent.vue", "content": "full file content" },
    { "path": "tests/my-scenarios.yaml", "content": "full yaml content" }
  ]
}

If no file changes are needed, use an empty edits array.

Existing project files:
{$filesBlock}

Context:
- Active file: {$activeFile}
- Editor mode: {$editorMode}
- Preview error: {$previewError}
- Selected test scenario file in runner: {$selectedScenarioFile}

Active file content:
{$contentBlock}
PROMPT;
    }

    private static function normalizeAiEditPath(?string $filePath): ?string
    {
        if ($filePath === null || $filePath === '') {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $filePath), './');
        if (!preg_match('/^(components\\/.+\\.vue|screens\\/.+\\.vue|index\\.js|tests\\/.+\\.ya?ml)$/i', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private static function callOpenAiCompatible(array $messages): string
    {
        $apiKey = env('VIBE_AI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException(
                'AI not configured. Set VIBE_AI_API_KEY or VIBE_AI_PROVIDER=cursor with the Cursor sidecar.'
            );
        }

        $baseUrl = rtrim(env('VIBE_AI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $model = env('VIBE_AI_MODEL', 'gpt-4o-mini');

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout(15)
                ->timeout(120)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('AI provider unreachable: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            $error = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("AI request failed: {$error}");
        }

        $content = $response->json('choices.0.message.content');
        if (!$content) {
            throw new \RuntimeException('Empty response from AI provider');
        }

        return $content;
    }

    private static function callCursorSidecar(array $messages, array $context): string
    {
        $sidecarUrl = rtrim(env('VIBE_CURSOR_AGENT_URL', 'http://127.0.0.1:4877'), '/');

        try {
            $response = Http::connectTimeout(10)
                ->timeout(180)
                ->post("{$sidecarUrl}/chat", [
                    'messages' => $messages,
                    'context' => $context,
                ]);
        } catch (ConnectionException $e) {
            throw new \RuntimeException(
                'Cursor sidecar unreachable. Start it with: npm run vibe:cursor-agent'
            );
        }

        if (!$response->successful()) {
            $error = $response->json('error') ?? $response->body();
            throw new \RuntimeException(
                "{$error}. Start the Cursor sidecar: npm run vibe:cursor-agent"
            );
        }

        return $response->json('content') ?? $response->json('message') ?? '';
    }

    private static function parseAgentResponse(string $rawText): array
    {
        $trimmed = trim($rawText);

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $trimmed, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'message' => $trimmed,
            'edits' => [],
        ];
    }

    private static function normalizeAgentResult(array $parsed): array
    {
        $message = is_string($parsed['message'] ?? null) ? $parsed['message'] : 'Done.';
        $edits = [];

        if (!empty($parsed['edits']) && is_array($parsed['edits'])) {
            foreach ($parsed['edits'] as $edit) {
                if (!is_array($edit)) {
                    continue;
                }
                if (!isset($edit['path'], $edit['content'])) {
                    continue;
                }
                $path = self::normalizeAiEditPath((string) $edit['path']);
                if ($path === null) {
                    continue;
                }
                $edits[] = [
                    'path' => $path,
                    'content' => (string) $edit['content'],
                ];
            }
        }

        return [
            'message' => $message,
            'edits' => $edits,
        ];
    }
}
