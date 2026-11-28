<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Clones screen controls from PM4 shipped screen JSON (same source the Screen Builder uses at runtime).
 * Prefers core templates under database/processes/screens/; falls back to MCP-bundled snapshots.
 */
final class ScreenControlCatalog
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $byComponent = null;

    /** @var array<int, string> */
    private const REQUIRED_COMPONENTS = [
        'FormInput',
        'FormTextArea',
        'FormSelect',
        'FormCheckbox',
        'FormDatePicker',
        'FormButton',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function cloneControl(string $component, array $config, string $label): array
    {
        self::boot();

        $template = self::$byComponent[$component] ?? null;
        if ($template === null) {
            throw new RuntimeException('No PM4 screen control template for component: ' . $component);
        }

        /** @var array<string, mixed> $item */
        $item = json_decode(json_encode($template, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $item['uuid'] = (string) Str::uuid();
        $item['label'] = $label;
        $item['component'] = $component;
        $item['config'] = array_replace_recursive($item['config'] ?? [], $config);

        return $item;
    }

    /**
     * @param  array<int, string>  $components
     * @return array<int, string>
     */
    public static function missingComponents(array $components = self::REQUIRED_COMPONENTS): array
    {
        self::boot();

        return array_values(array_filter(
            $components,
            fn (string $component): bool => !isset(self::$byComponent[$component])
        ));
    }

    /**
     * @return array<string, array{source: string, inspector_fields: int}>
     */
    public static function catalogReport(): array
    {
        self::boot();

        $report = [];
        foreach (self::$byComponent as $component => $item) {
            $report[$component] = [
                'source' => (string) ($item['_mcp_source'] ?? 'unknown'),
                'inspector_fields' => is_array($item['inspector'] ?? null) ? count($item['inspector']) : 0,
            ];
        }

        ksort($report);

        return $report;
    }

    public static function inspectorFor(string $component): array
    {
        self::boot();

        $inspector = self::$byComponent[$component]['inspector'] ?? null;

        return is_array($inspector) ? $inspector : [];
    }

    public static function editorComponentFor(string $component): string
    {
        self::boot();

        $value = self::$byComponent[$component]['editor-component'] ?? null;

        return is_string($value) && $value !== '' ? $value : $component;
    }

    public static function editorControlFor(string $component): string
    {
        self::boot();

        $value = self::$byComponent[$component]['editor-control'] ?? null;

        return is_string($value) && $value !== '' ? $value : self::editorComponentFor($component);
    }

    private static function boot(): void
    {
        if (self::$byComponent !== null) {
            return;
        }

        self::$byComponent = [];

        foreach (glob(database_path('processes/screens/*.json')) ?: [] as $path) {
            self::indexFile($path, 'core:' . basename($path));
        }

        self::indexFallbackFile(__DIR__ . '/screen-control-fallbacks.json', 'mcp:fallbacks');
    }

    private static function indexFile(string $path, string $source): void
    {
        if (!is_readable($path)) {
            return;
        }

        $pages = json_decode((string) file_get_contents($path), true);
        if (!is_array($pages)) {
            return;
        }

        // screen_package format
        if (isset($pages['screens']) && is_array($pages['screens'])) {
            foreach ($pages['screens'] as $screen) {
                if (!is_array($screen['config'] ?? null)) {
                    continue;
                }
                foreach ($screen['config'] as $page) {
                    self::indexPage($page, $source);
                }
            }

            return;
        }

        foreach ($pages as $page) {
            self::indexPage($page, $source);
        }
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private static function indexPage(array $page, string $source): void
    {
        foreach ($page['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $component = $item['component'] ?? null;
            if (!is_string($component) || $component === '') {
                continue;
            }

            self::registerTemplate($component, $item, $source);
        }
    }

    private static function indexFallbackFile(string $path, string $source): void
    {
        if (!is_readable($path)) {
            return;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data['controls'] ?? null)) {
            return;
        }

        foreach ($data['controls'] as $component => $item) {
            if (!is_string($component) || !is_array($item)) {
                continue;
            }

            self::registerTemplate($component, $item, $source);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function registerTemplate(string $component, array $item, string $source): void
    {
        $item['_mcp_source'] = $source;
        $existing = self::$byComponent[$component] ?? null;

        if ($existing === null || self::templateScore($item) > self::templateScore($existing)) {
            self::$byComponent[$component] = $item;
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function templateScore(array $item): int
    {
        $score = is_array($item['inspector'] ?? null) ? count($item['inspector']) * 10 : 0;

        if (isset($item['editor-control'])) {
            $score += 5;
        }
        if (isset($item['editor-component'])) {
            $score += 5;
        }
        if (isset($item['config']['dataFormat'])) {
            $score += 3;
        }
        if (($item['config']['defaultSubmit'] ?? false) === true) {
            $score += 3;
        }

        return $score;
    }
}
