<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;

class Analyzer
{
    public function __construct(
        private readonly Reader $reader,
        private readonly Writer $writer,
        private readonly ScreenBuilder $screenBuilder,
    ) {
    }

    /**
     * @return array{
     *     process_id: string,
     *     name: string,
     *     score: int,
     *     findings: array<int, array<string, mixed>>,
     *     suggestions: array<int, string>,
     *     validation: array<string, mixed>
     * }
     */
    public function analyze(string $processId): array
    {
        $process = Process::findOrFail($processId);
        $inventory = $this->reader->getProcessInventory($processId);
        $validation = $this->writer->validateProcess($processId);
        $findings = [];
        $suggestions = [];

        if (($validation['valid'] ?? false) !== true) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'bpmn_invalid',
                'message' => 'BPMN validation failed.',
                'details' => $validation['errors'] ?? [],
            ];
            $suggestions[] = 'Fix BPMN validation errors with update_process_bpmn or bpmn design tools.';
        }

        foreach ($inventory['linked_assets']['tasks_without_screen'] ?? [] as $task) {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'task_without_screen',
                'message' => 'Task "' . ($task['name'] ?: $task['element_id']) . '" has no screen linked.',
                'element_id' => $task['element_id'],
            ];
            $suggestions[] = 'Link a screen to element ' . $task['element_id'] . ' with link_screen_to_task or create_screen_from_fields.';
        }

        foreach ($inventory['linked_assets']['screens'] ?? [] as $linkedScreen) {
            $screenId = (string) ($linkedScreen['screen_id'] ?? '');
            if ($screenId === '') {
                continue;
            }

            $screen = Screen::find($screenId);
            if ($screen === null) {
                $findings[] = [
                    'severity' => 'error',
                    'code' => 'linked_screen_not_found',
                    'message' => 'Linked screen ID ' . $screenId . ' was not found.',
                    'screen_id' => $screenId,
                ];
                continue;
            }

            $validation = $this->screenBuilder->validateScreenConfig(is_array($screen->config) ? $screen->config : []);
            foreach ($validation['issues'] as $issue) {
                $findings[] = [
                    'severity' => $issue['severity'] ?? 'warning',
                    'code' => $issue['code'] ?? 'screen_config_issue',
                    'message' => ($issue['message'] ?? 'Screen config issue') . ' (screen "' . $screen->title . '" #' . $screenId . ')',
                    'screen_id' => $screenId,
                    'path' => $issue['path'] ?? null,
                ];
            }

            if (($validation['valid'] ?? false) !== true) {
                $suggestions[] = 'Fix screen "' . $screen->title . '" (#' . $screenId . '): recreate with create_screen_from_fields or update_screen when available.';
            }
        }

        foreach ($inventory['linked_assets']['script_tasks'] ?? [] as $scriptTask) {
            if ($scriptTask['script_id'] === null) {
                $findings[] = [
                    'severity' => 'warning',
                    'code' => 'script_task_without_script',
                    'message' => 'Script task "' . ($scriptTask['name'] ?: $scriptTask['element_id']) . '" has no scriptRef.',
                    'element_id' => $scriptTask['element_id'],
                ];
                $suggestions[] = 'Create a script and link it with link_script_to_task for ' . $scriptTask['element_id'] . '.';
            }
        }

        if (($inventory['counts']['tasks'] ?? 0) === 0 && ($inventory['counts']['script_tasks'] ?? 0) === 0) {
            $findings[] = [
                'severity' => 'info',
                'code' => 'no_human_tasks',
                'message' => 'Process has no user tasks; it may be start-to-end only or script-only.',
            ];
            $suggestions[] = 'Add user tasks with add_bpmn_user_task to build a workable approval/data flow.';
        }

        if (($inventory['counts']['variables'] ?? 0) === 0) {
            $findings[] = [
                'severity' => 'info',
                'code' => 'no_variables',
                'message' => 'No process variables defined in process.properties.',
            ];
            $suggestions[] = 'Add variables with apply_process_variables if the process needs request data defaults.';
        }

        if ($process->status !== 'ACTIVE') {
            $findings[] = [
                'severity' => 'warning',
                'code' => 'process_not_active',
                'message' => 'Process status is ' . $process->status . '; new requests may not be startable.',
            ];
            $suggestions[] = 'Set status to ACTIVE with update_process when ready for use.';
        }

        if ($process->categories()->count() === 0) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'missing_process_category',
                'message' => 'Process has no category; it will not appear in Launchpad (All Processes).',
            ];
            $suggestions[] = 'Assign process_category_id or recreate with MCP after ensureProcessIsStartable fix.';
        }

        if ($process->usersCanStart()->count() === 0 && $process->groupsCanStart()->count() === 0) {
            $findings[] = [
                'severity' => 'error',
                'code' => 'missing_start_permissions',
                'message' => 'No users or groups can start this process (non-admin users cannot start it).',
            ];
            $suggestions[] = 'Configure start event assignment in BPMN or call ensureProcessIsStartable via Writer/MCP recreate.';
        }

        $errorCount = count(array_filter($findings, fn (array $f): bool => ($f['severity'] ?? '') === 'error'));
        $warningCount = count(array_filter($findings, fn (array $f): bool => ($f['severity'] ?? '') === 'warning'));
        $linkedScreenCount = count($inventory['linked_assets']['screens'] ?? []);
        $totalChecks = max(1, ($inventory['counts']['tasks'] ?? 0) + $linkedScreenCount + 3);
        $score = (int) max(0, min(100, round((1 - ($errorCount * 2 + $warningCount) / $totalChecks) * 100)));

        return [
            'process_id' => $process->id,
            'name' => $process->name,
            'score' => $score,
            'findings' => $findings,
            'suggestions' => array_values(array_unique($suggestions)),
            'validation' => $validation,
            'inventory' => [
                'counts' => $inventory['counts'],
                'tasks_without_screen' => count($inventory['linked_assets']['tasks_without_screen'] ?? []),
            ],
        ];
    }
}
