<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Platform;

use Illuminate\Support\Str;

/**
 * Builds PM4 screen_package JSON consumed by ImportScreen (same format as ExportScreen).
 */
final class ScreenPackageBuilder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function encode(array $data): string
    {
        $package = [
            'type' => 'screen_package',
            'version' => '2',
            'screens' => [$this->screenObject($data)],
        ];

        return json_encode($package, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function screenObject(array $data): array
    {
        $config = $data['config'] ?? $data['config_json'] ?? [];

        return [
            'id' => 'mcp_' . Str::uuid()->toString(),
            'title' => (string) ($data['title'] ?? 'Screen'),
            'description' => (string) ($data['description'] ?? $data['title'] ?? 'Screen'),
            'type' => (string) ($data['type'] ?? 'FORM'),
            'config' => is_array($config) ? $config : [],
            'computed' => is_array($data['computed'] ?? null) ? $data['computed'] : [],
            'watchers' => is_array($data['watchers'] ?? null) ? $data['watchers'] : [],
            'custom_css' => (string) ($data['custom_css'] ?? ''),
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'categories' => [],
        ];
    }
}
