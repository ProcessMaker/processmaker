<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp;

/**
 * Built-in MCP skills shipped with ProcessMaker. Seeded into mcp.skills and
 * protected in the admin UI; tenants may add more custom skills alongside them.
 */
final class McpSystemSkills
{
    public const SCREEN_CREATION_RULES_ID = 'mcp-system-screen-creation-rules';

    public const SCREEN_CREATION_RULES_SLUG = 'screen-creation-rules';

    public const PROCESS_CREATION_RULES_ID = 'mcp-system-process-creation-rules';

    public const PROCESS_CREATION_RULES_SLUG = 'process-creation-rules';

    public const DATA_CONNECTOR_CREATION_RULES_ID = 'mcp-system-data-connector-creation-rules';

    public const DATA_CONNECTOR_CREATION_RULES_SLUG = 'data-connector-creation-rules';

    public const COLLECTION_CREATION_RULES_ID = 'mcp-system-collection-creation-rules';

    public const COLLECTION_CREATION_RULES_SLUG = 'collection-creation-rules';

    /**
     * Legacy ids/slugs previously stored in local mcp.skills configs.
     *
     * @var list<string>
     */
    private const LEGACY_SCREEN_CREATION_KEYS = [
        'skill-1793976475109-l6si55',
    ];

    /**
     * @return list<array{id: string, name: string, slug: string, when: string, file: string, scope: string}>
     */
    private static function catalog(): array
    {
        return [
            [
                'id' => self::SCREEN_CREATION_RULES_ID,
                'name' => 'Screen creation rules',
                'slug' => self::SCREEN_CREATION_RULES_SLUG,
                'when' => 'Before create-screen, update-screen, or validate-screen',
                'file' => 'screen-creation-rules.md',
                'scope' => McpGuidance::SCOPE_SCREENS,
            ],
            [
                'id' => self::PROCESS_CREATION_RULES_ID,
                'name' => 'Process creation rules',
                'slug' => self::PROCESS_CREATION_RULES_SLUG,
                'when' => 'Before create-process, update-process, or authoring BPMN',
                'file' => 'process-creation-rules.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-planner',
                'name' => 'Process planner',
                'slug' => 'process-planner',
                'when' => 'When designing a new process from a vague request — before create-process; interview tasks, decisions, assignees',
                'file' => 'process-planner.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-start-event',
                'name' => 'Process node: Start Event',
                'slug' => 'process-node-start-event',
                'when' => 'When adding or configuring a Start Event (blank, timer, message, signal, conditional)',
                'file' => 'process-node-start-event.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-end-event',
                'name' => 'Process node: End Event',
                'slug' => 'process-node-end-event',
                'when' => 'When adding or configuring an End, Terminate, Message, Signal, or Error End Event',
                'file' => 'process-node-end-event.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-form-task',
                'name' => 'Process node: Form Task',
                'slug' => 'process-node-form-task',
                'when' => 'When adding or configuring a Form Task (bpmn:Task)',
                'file' => 'process-node-form-task.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-manual-task',
                'name' => 'Process node: Manual Task',
                'slug' => 'process-node-manual-task',
                'when' => 'When adding or configuring a Manual Task (bpmn:ManualTask)',
                'file' => 'process-node-manual-task.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-script-task',
                'name' => 'Process node: Script Task',
                'slug' => 'process-node-script-task',
                'when' => 'When adding or configuring a Script Task (bpmn:ScriptTask)',
                'file' => 'process-node-script-task.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-call-activity',
                'name' => 'Process node: Call Activity',
                'slug' => 'process-node-call-activity',
                'when' => 'When adding or configuring a Call Activity / Sub Process',
                'file' => 'process-node-call-activity.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-gateways',
                'name' => 'Process node: Gateways',
                'slug' => 'process-node-gateways',
                'when' => 'When adding or configuring Exclusive, Inclusive, Parallel, or Event-based gateways',
                'file' => 'process-node-gateways.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-sequence-flow',
                'name' => 'Process node: Sequence Flow',
                'slug' => 'process-node-sequence-flow',
                'when' => 'When connecting nodes or configuring gateway conditions on sequence flows',
                'file' => 'process-node-sequence-flow.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-intermediate-events',
                'name' => 'Process node: Intermediate Events',
                'slug' => 'process-node-intermediate-events',
                'when' => 'When adding intermediate timer, message, signal, or conditional catch/throw events',
                'file' => 'process-node-intermediate-events.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-boundary-events',
                'name' => 'Process node: Boundary Events',
                'slug' => 'process-node-boundary-events',
                'when' => 'When attaching boundary timer, message, error, signal, conditional, or escalation events',
                'file' => 'process-node-boundary-events.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-node-service-task',
                'name' => 'Process node: Service Task',
                'slug' => 'process-node-service-task',
                'when' => 'When adding a Service Task or package connector (email, AI, data connector, PDF, …)',
                'file' => 'process-node-service-task.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-web-entry',
                'name' => 'Process Web Entry',
                'slug' => 'process-web-entry',
                'when' => 'When configuring Web Entry on a Start Event or Form Task',
                'file' => 'process-web-entry.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-validation',
                'name' => 'Process validation',
                'slug' => 'process-validation',
                'when' => 'Before deploy/create/update of a process, or when validating BPMN',
                'file' => 'process-validation.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => 'mcp-system-process-bpmn-layout',
                'name' => 'Process BPMN layout',
                'slug' => 'process-bpmn-layout',
                'when' => 'When authoring BPMN for MCP or calling layout-bpmn / auto_layout_bpmn on create-update',
                'file' => 'process-bpmn-layout.md',
                'scope' => McpGuidance::SCOPE_PROCESSES,
            ],
            [
                'id' => self::DATA_CONNECTOR_CREATION_RULES_ID,
                'name' => 'Data Connector creation rules',
                'slug' => self::DATA_CONNECTOR_CREATION_RULES_SLUG,
                'when' => 'Before create-data-connector or update-data-connector',
                'file' => 'data-connector-creation-rules.md',
                'scope' => McpGuidance::SCOPE_DATA_CONNECTORS,
            ],
            [
                'id' => 'mcp-system-data-connector-in-process',
                'name' => 'Data Connector in a process',
                'slug' => 'data-connector-in-process',
                'when' => 'When wiring a Data Connector into BPMN Service Task pm:config, or configuring process API calls',
                'file' => 'data-connector-in-process.md',
                'scope' => McpGuidance::SCOPE_DATA_CONNECTORS,
            ],
            [
                'id' => self::COLLECTION_CREATION_RULES_ID,
                'name' => 'Collection creation rules',
                'slug' => self::COLLECTION_CREATION_RULES_SLUG,
                'when' => 'Before create-collection or update-collection',
                'file' => 'collection-creation-rules.md',
                'scope' => McpGuidance::SCOPE_DATA_CONNECTORS,
            ],
            [
                'id' => 'mcp-system-collection-test-data',
                'name' => 'Collection test data',
                'slug' => 'collection-test-data',
                'when' => 'When adding sample/demo records with create-collection-records',
                'file' => 'collection-test-data.md',
                'scope' => McpGuidance::SCOPE_DATA_CONNECTORS,
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string, slug: string, when: string, content: string, system: true, scope: string}>
     */
    public static function definitions(): array
    {
        $definitions = [];
        foreach (self::catalog() as $skill) {
            $definitions[] = [
                'id' => $skill['id'],
                'name' => $skill['name'],
                'slug' => $skill['slug'],
                'when' => $skill['when'],
                'content' => self::loadMarkdown($skill['file']),
                'system' => true,
                'scope' => $skill['scope'],
            ];
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public static function reservedKeys(): array
    {
        $keys = self::LEGACY_SCREEN_CREATION_KEYS;
        foreach (self::catalog() as $skill) {
            $keys[] = $skill['id'];
            $keys[] = $skill['slug'];
        }

        return array_values(array_unique($keys));
    }

    private static function loadMarkdown(string $filename): string
    {
        $path = __DIR__ . '/skills/' . $filename;
        if (!is_file($path)) {
            return '';
        }

        return trim((string) file_get_contents($path));
    }
}
