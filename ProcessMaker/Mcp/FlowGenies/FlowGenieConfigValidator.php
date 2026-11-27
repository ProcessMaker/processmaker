<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\FlowGenies;

use Illuminate\Validation\ValidationException;

class FlowGenieConfigValidator
{
    /**
     * @param  array<string, mixed>|null  $config
     * @return array{ok: bool, errors: list<array{code: string, message: string, path?: string}>, warnings: list<array{code: string, message: string, path?: string}>}
     */
    public function validate(?array $config): array
    {
        $errors = [];
        $warnings = [];

        if ($config === null) {
            return [
                'ok' => true,
                'errors' => [],
                'warnings' => [[
                    'code' => 'config_empty',
                    'message' => 'No config provided. create/update will apply agent defaults (instructions empty until set).',
                ]],
            ];
        }

        if ($config === []) {
            $warnings[] = [
                'code' => 'config_empty',
                'message' => 'Empty config object. Prefer instructions + model + output_type for runnable FlowGenies.',
            ];

            return ['ok' => true, 'errors' => $errors, 'warnings' => $warnings];
        }

        $instructions = $this->instructionsText($config);
        if ($instructions === '') {
            $warnings[] = [
                'code' => 'instructions_missing',
                'message' => 'Set config.instructions (or legacy config.prompt). Runtime agents need a system prompt.',
                'path' => 'config.instructions',
            ];
        }

        if (isset($config['output_type']) && isset($config['response_format']) && $config['output_type'] !== $config['response_format']) {
            $warnings[] = [
                'code' => 'response_format_alias',
                'message' => 'Both output_type and response_format are set. Prefer output_type (Designer key); response_format is normalized into it.',
                'path' => 'config.output_type',
            ];
        }

        if (isset($config['mcp_servers']) && !is_array($config['mcp_servers'])) {
            $errors[] = [
                'code' => 'mcp_servers_invalid',
                'message' => 'config.mcp_servers must be an array.',
                'path' => 'config.mcp_servers',
            ];
        }

        if (isset($config['tools']) && !is_array($config['tools'])) {
            $errors[] = [
                'code' => 'tools_invalid',
                'message' => 'config.tools must be an array.',
                'path' => 'config.tools',
            ];
        }

        if (isset($config['guardrails']) && !is_array($config['guardrails'])) {
            $errors[] = [
                'code' => 'guardrails_invalid',
                'message' => 'config.guardrails must be an object/array.',
                'path' => 'config.guardrails',
            ];
        }

        if (isset($config['model_settings']) && !is_array($config['model_settings'])) {
            $errors[] = [
                'code' => 'model_settings_invalid',
                'message' => 'config.model_settings must be an object/array.',
                'path' => 'config.model_settings',
            ];
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     */
    public function normalize(?array $config): array
    {
        $config = is_array($config) ? $config : [];
        $result = $this->validate($config);
        if (!$result['ok']) {
            $messages = [];
            foreach ($result['errors'] as $error) {
                $messages[$error['path'] ?? 'config'][] = $error['message'];
            }
            throw ValidationException::withMessages($messages);
        }

        if (isset($config['prompt']) && !isset($config['instructions'])) {
            $config['instructions'] = $config['prompt'];
            unset($config['prompt']);
        }

        if (isset($config['response_format']) && !isset($config['output_type'])) {
            $config['output_type'] = $this->normalizeOutputType((string) $config['response_format']);
            unset($config['response_format']);
        } elseif (isset($config['responseFormat']) && !isset($config['output_type'])) {
            $config['output_type'] = $this->normalizeOutputType((string) $config['responseFormat']);
            unset($config['responseFormat']);
        } elseif (isset($config['output_type'])) {
            $config['output_type'] = $this->normalizeOutputType((string) $config['output_type']);
        }

        return array_replace($this->defaults(), $config);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaultModel = config('ai.default_model');
        if (!is_string($defaultModel) || $defaultModel === '') {
            $defaultModel = 'gpt-4o';
        }

        return [
            'instructions' => '',
            'model' => $defaultModel,
            'provider' => 'openai',
            'provider_api_key' => '',
            'output_type' => 'Text (default)',
            'mcp_servers' => [],
            'tools' => [],
            'dynamic_tools' => [],
            'collections' => [],
            'subagents' => [],
            'max_turns' => 30,
            'limit' => 15,
            'match_threshold' => 0.3,
            'enhance_pdf_quality' => false,
            'model_settings' => [
                'temperature' => 0.5,
                'top_p' => 1,
                'tool_choice' => 'auto',
                'parallel_tool_calls' => false,
            ],
            'guardrails' => [
                'enabled' => true,
                'policies' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function instructionsText(array $config): string
    {
        if (isset($config['instructions'])) {
            return trim((string) $config['instructions']);
        }
        if (isset($config['prompt'])) {
            return trim((string) $config['prompt']);
        }

        return '';
    }

    private function normalizeOutputType(string $value): string
    {
        $trimmed = trim($value);
        $map = [
            'text' => 'Text (default)',
            'json' => 'JSON',
            'table' => 'Table',
            'Text (default)' => 'Text (default)',
            'JSON' => 'JSON',
            'Table' => 'Table',
        ];

        return $map[$trimmed] ?? $trimmed;
    }
}
