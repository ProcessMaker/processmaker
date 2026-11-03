<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use ProcessMaker\Mcp\McpSupport;
use ProcessMaker\Mcp\Platform\PlatformWriter;
use ProcessMaker\Models\Process;

class ProcessVariableMigrator
{
    /**
     * @param  array<int, mixed>  $variables
     * @return array{applied: array<int, string>, warnings: array<int, string>}
     */
    public function apply(Process $process, array $variables): array
    {
        $catalog = [];
        $defaults = [];
        $applied = [];
        $warnings = [];

        foreach ($variables as $variable) {
            if (!is_array($variable)) {
                continue;
            }

            $name = trim((string) ($variable['var_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $type = $this->mapType($variable['var_field_type'] ?? null);
            $default = $variable['var_default'] ?? null;

            $catalog[] = [
                'name' => $name,
                'label' => (string) ($variable['var_label'] ?? $name),
                'type' => $type,
                'default' => $default,
                'pm3_uid' => $variable['var_uid'] ?? null,
            ];

            if ($default !== null && $default !== '') {
                $defaults[$name] = $this->castDefault($default, $type);
            }

            if (!empty($variable['var_sql'])) {
                $warnings[] = 'Variable ' . $name . ' uses SQL and needs manual datasource setup.';
            }

            $applied[] = $name;
        }

        if ($catalog === []) {
            return ['applied' => [], 'warnings' => $warnings];
        }

        $properties = is_array($process->properties) ? $process->properties : [];
        $properties['variables'] = $catalog;

        if ($defaults !== []) {
            $properties['request_data_defaults'] = $defaults;
        }

        McpSupport::ensureActingUser($process);
        app(PlatformWriter::class)->updateProcess($process, ['properties' => $properties]);
        $process->refresh();

        return ['applied' => $applied, 'warnings' => $warnings];
    }

    private function mapType(mixed $fieldType): string
    {
        $type = strtoupper(trim((string) $fieldType));

        return match ($type) {
            'INTEGER', 'INT' => 'integer',
            'FLOAT', 'DOUBLE', 'REAL' => 'float',
            'BOOLEAN', 'BOOL' => 'boolean',
            'GRID', 'ARRAY' => 'array',
            default => 'string',
        };
    }

    private function castDefault(mixed $default, string $type): mixed
    {
        if (!is_string($default)) {
            return $default;
        }

        return match ($type) {
            'integer' => (int) $default,
            'float' => (float) $default,
            'boolean' => filter_var($default, FILTER_VALIDATE_BOOLEAN),
            'array' => $this->decodeArrayDefault($default),
            default => $default,
        };
    }

    /**
     * @return array<int|string, mixed>|string
     */
    private function decodeArrayDefault(string $default): array|string
    {
        $decoded = json_decode($default, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
