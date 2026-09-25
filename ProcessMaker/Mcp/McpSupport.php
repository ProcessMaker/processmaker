<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Mcp\Platform\PlatformWriter;
use ProcessMaker\Mcp\Process\ScreenControlCatalog;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;

/**
 * Shared defaults for MCP-created assets. Single place to adjust labels/categories.
 */
final class McpSupport
{
    public const MCP_VERSION = '1.2.0';

    public const SCREEN_CATEGORY = 'MCP';

    public const PROCESS_CATEGORY = 'MCP';

    public const SCRIPT_CATEGORY = 'MCP';

    public static function ensureActingUser(?Process $process = null): void
    {
        if (Auth::user() instanceof User) {
            return;
        }

        if ($process?->user_id) {
            Auth::setUser(User::findOrFail($process->user_id));

            return;
        }

        Auth::setUser(User::firstOrFail());
    }

    /**
     * Features the MCP layer implements today. Update this list when adding tools.
     *
     * @return array<int, string>
     */
    public static function supportedFeatures(): array
    {
        return [
            'migration_pm3_bundle',
            'migration_log_resume',
            'process_read',
            'process_analyze',
            'process_design_plan',
            'process_bpmn_user_task',
            'process_bpmn_sequence_flow',
            'process_bpmn_gateway',
            'screen_from_fields',
            'screen_config_validation',
            'platform_writer',
            'process_variables',
            'screen_script_link',
            'bpmn_validate',
        ];
    }

    /**
     * Platform integration points checked at runtime. If a check fails, PM core changed and MCP may need updates.
     *
     * @return array<int, array{id: string, label: string, ok: bool, detail: string}>
     */
    public static function platformChecks(): array
    {
        $checks = [];

        $checks[] = self::classCheck('process_model', Process::class, ['rules', 'getDefinitions']);
        $checks[] = self::classCheck('screen_model', \ProcessMaker\Models\Screen::class, ['rules']);
        $checks[] = self::classCheck('script_model', \ProcessMaker\Models\Script::class, ['rules']);
        $checks[] = self::classCheck('bpmn_document', \ProcessMaker\Nayra\Storage\BpmnDocument::class, ['validateBPMNSchema']);
        $checks[] = self::classCheck('fix_bpmn_schema', \ProcessMaker\Nayra\Services\FixBpmnSchemaService::class, ['fix']);
        $checks[] = self::classCheck('bpmn_validation_rule', \ProcessMaker\Rules\BPMNValidation::class, ['passes']);

        $xsdPath = public_path('definitions/ProcessMaker.xsd');
        $checks[] = [
            'id' => 'bpmn_xsd',
            'label' => 'BPMN XSD schema file',
            'ok' => is_readable($xsdPath),
            'detail' => is_readable($xsdPath) ? $xsdPath : 'Missing: public/definitions/ProcessMaker.xsd',
        ];

        $template = database_path('processes/templates/SingleTask.bpmn');
        $checks[] = [
            'id' => 'bpmn_template_single_task',
            'label' => 'SingleTask BPMN template',
            'ok' => is_readable($template),
            'detail' => is_readable($template) ? $template : 'Missing SingleTask.bpmn template',
        ];

        $checks[] = [
            'id' => 'collections_package',
            'label' => 'Collections plugin (optional, for report tables)',
            'ok' => class_exists(\ProcessMaker\Plugins\Collections\Models\Collection::class),
            'detail' => class_exists(\ProcessMaker\Plugins\Collections\Models\Collection::class)
                ? 'Available'
                : 'Not installed; collections migration step will warn',
        ];

        $checks[] = [
            'id' => 'laravel_mcp',
            'label' => 'Laravel MCP server',
            'ok' => class_exists(\Laravel\Mcp\Server\Tool::class),
            'detail' => class_exists(\Laravel\Mcp\Server\Tool::class) ? 'Available' : 'laravel/mcp not installed',
        ];

        $checks[] = [
            'id' => 'screen_control_catalog',
            'label' => 'MCP screen control templates (PM4 shipped screens)',
            'ok' => ScreenControlCatalog::missingComponents() === [],
            'detail' => ScreenControlCatalog::missingComponents() === []
                ? 'All required control templates indexed'
                : 'Missing templates: ' . implode(', ', ScreenControlCatalog::missingComponents()),
        ];

        $checks[] = self::classCheck('platform_writer', PlatformWriter::class, [
            'createProcess',
            'createScreen',
            'updateProcessBpmn',
            'createScript',
        ]);
        $checks[] = self::classCheck('screen_config_normalizer', \ProcessMaker\Mcp\Process\ScreenConfigNormalizer::class, [
            'normalize',
            'normalizeItem',
        ]);
        $checks[] = self::classCheck('import_screen_job', \ProcessMaker\Jobs\ImportScreen::class, ['handle']);

        return $checks;
    }

    /**
     * @param  array<int, string>  $methods
     * @return array{id: string, label: string, ok: bool, detail: string}
     */
    private static function classCheck(string $id, string $class, array $methods): array
    {
        if (!class_exists($class)) {
            return [
                'id' => $id,
                'label' => $class,
                'ok' => false,
                'detail' => 'Class not found',
            ];
        }

        $missing = [];
        foreach ($methods as $method) {
            if (!method_exists($class, $method)) {
                $missing[] = $method;
            }
        }

        return [
            'id' => $id,
            'label' => $class,
            'ok' => $missing === [],
            'detail' => $missing === [] ? 'OK' : 'Missing methods: ' . implode(', ', $missing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function report(): array
    {
        $checks = self::platformChecks();
        $failed = array_values(array_filter($checks, fn (array $c): bool => !$c['ok']));
        $requiredFailed = array_values(array_filter(
            $failed,
            fn (array $c): bool => $c['id'] !== 'collections_package'
        ));

        return [
            'mcp_version' => self::MCP_VERSION,
            'pm_version' => self::pmVersion(),
            'supported_features' => self::supportedFeatures(),
            'platform_checks' => $checks,
            'platform_drift_detected' => $requiredFailed !== [],
            'platform_drift' => array_map(fn (array $c): array => [
                'id' => $c['id'],
                'detail' => $c['detail'],
            ], $requiredFailed),
            'optional_gaps' => array_map(fn (array $c): array => [
                'id' => $c['id'],
                'detail' => $c['detail'],
            ], array_filter($failed, fn (array $c): bool => $c['id'] === 'collections_package')),
            'not_supported_by_mcp' => [
                'full_platform_parity' => 'MCP covers migration, basic design, and analysis — not every PM4 UI feature.',
                'user_group_sync' => 'User/group assignment is metadata only; configure in PM4 UI.',
                'runtime_case_testing' => 'Starting requests and task completion is not exposed yet.',
                'advanced_bpmn_layout' => 'PM3 canvas positions migrate via BPMNDiagram; PM4 only auto-layouts when diagram is missing.',
            ],
            'screen_control_catalog' => ScreenControlCatalog::catalogReport(),
        ];
    }

    private static function pmVersion(): string
    {
        $composer = base_path('composer.json');
        if (!is_readable($composer)) {
            return 'unknown';
        }

        $data = json_decode(file_get_contents($composer), true);

        return is_array($data) ? (string) ($data['version'] ?? 'unknown') : 'unknown';
    }
}
