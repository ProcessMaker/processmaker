<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

use ProcessMaker\Mcp\Migration\ProcessVariableMigrator;
use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Models\Process;

class Designer
{
    public function __construct(
        private readonly Writer $writer,
        private readonly ScreenBuilder $screenBuilder,
        private readonly BpmnDesigner $bpmnDesigner,
        private readonly Analyzer $analyzer,
    ) {
    }

    /**
     * Create a process from a structured design plan.
     *
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    public function applyDesignPlan(array $plan): array
    {
        $processData = is_array($plan['process'] ?? null) ? $plan['process'] : [];
        $bpmnTemplate = (string) ($plan['bpmn_template'] ?? 'SingleTask');

        $processPayload = [
            'name' => $processData['name'] ?? 'New Process',
            'description' => $processData['description'] ?? $processData['name'] ?? 'Created via MCP agent',
            'status' => $processData['status'] ?? 'ACTIVE',
            'process_category_id' => $processData['process_category_id'] ?? null,
            'bpmn' => $this->loadBpmnTemplate($bpmnTemplate),
        ];

        $process = $this->writer->createProcess(array_filter($processPayload, fn ($v) => $v !== null));
        $processId = (string) $process->id;

        $linkedScripts = [];
        $addedTasks = [];

        foreach ($plan['tasks'] ?? [] as $taskPlan) {
            if (!is_array($taskPlan)) {
                continue;
            }

            $added = $this->bpmnDesigner->addUserTask(
                $processId,
                (string) ($taskPlan['name'] ?? 'Task'),
                is_string($taskPlan['element_id'] ?? null) ? $taskPlan['element_id'] : null,
                is_string($taskPlan['insert_after_element_id'] ?? null) ? $taskPlan['insert_after_element_id'] : null,
            );

            $addedTasks[] = [
                'element_id' => $added['element_id'],
                'screen_index' => $taskPlan['screen_index'] ?? null,
            ];
        }

        $screens = [];
        foreach ($plan['screens'] ?? [] as $screenPlan) {
            if (!is_array($screenPlan)) {
                continue;
            }

            $title = (string) ($screenPlan['title'] ?? 'Form');
            $config = isset($screenPlan['config_json'])
                ? $screenPlan['config_json']
                : $this->screenBuilder->buildFormConfig($screenPlan['fields'] ?? [], $title);

            $screen = $this->writer->createScreen([
                'title' => $screenPlan['title'] ?? 'Form',
                'description' => $screenPlan['description'] ?? $screenPlan['title'] ?? 'Form',
                'config_json' => $config,
            ]);

            $screens[] = [
                'id' => $screen->id,
                'title' => $screen->title,
                'link_to_element' => $screenPlan['link_to_element'] ?? null,
            ];
        }

        $scripts = [];
        foreach ($plan['scripts'] ?? [] as $scriptPlan) {
            if (!is_array($scriptPlan)) {
                continue;
            }

            $script = $this->writer->createScript([
                'title' => $scriptPlan['title'] ?? 'Script',
                'description' => $scriptPlan['description'] ?? $scriptPlan['title'] ?? 'Script',
                'code' => $scriptPlan['code'] ?? '<?php return [];',
                'language' => $scriptPlan['language'] ?? 'php',
            ]);

            $scripts[] = [
                'id' => $script->id,
                'title' => $script->title,
                'link_to_element' => $scriptPlan['link_to_element'] ?? null,
            ];
        }

        $linkedScreens = [];
        foreach ($screens as $screen) {
            $elementId = $screen['link_to_element'] ?? $this->defaultUserTaskElementId($processId);
            if ($elementId === null) {
                continue;
            }

            $this->writer->linkScreenToTask($processId, $elementId, (string) $screen['id']);
            $linkedScreens[] = [
                'screen_id' => $screen['id'],
                'element_id' => $elementId,
            ];
        }

        foreach ($scripts as $script) {
            if ($script['link_to_element'] === null) {
                continue;
            }

            $this->writer->linkScriptToTask($processId, (string) $script['link_to_element'], (string) $script['id']);
            $linkedScripts[] = [
                'script_id' => $script['id'],
                'element_id' => $script['link_to_element'],
            ];
        }

        $variableResult = ['applied' => [], 'warnings' => []];
        if (($plan['variables'] ?? []) !== []) {
            $process->refresh();
            $variableResult = (new ProcessVariableMigrator())->apply($process, $plan['variables']);
        }

        foreach ($addedTasks as $addedTask) {
            if ($addedTask['screen_index'] === null || !isset($screens[(int) $addedTask['screen_index']])) {
                continue;
            }

            $screenId = (string) $screens[(int) $addedTask['screen_index']]['id'];
            $this->writer->linkScreenToTask($processId, (string) $addedTask['element_id'], $screenId);
            $linkedScreens[] = ['screen_id' => $screenId, 'element_id' => $addedTask['element_id']];
        }

        $validation = $this->writer->validateProcess($processId);
        $analysis = $this->analyzer->analyze($processId);

        return [
            'process_id' => $process->id,
            'process_name' => $process->name,
            'screens' => $screens,
            'scripts' => $scripts,
            'linked_screens' => $linkedScreens,
            'linked_scripts' => $linkedScripts,
            'applied_variables' => $variableResult['applied'] ?? [],
            'warnings' => $variableResult['warnings'] ?? [],
            'validation' => $validation,
            'analysis' => [
                'score' => $analysis['score'],
                'findings_count' => count($analysis['findings']),
                'suggestions' => $analysis['suggestions'],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     * @return array{applied: array<int, mixed>, warnings: array<int, string>}
     */
    public function applyVariables(string $processId, array $variables): array
    {
        $process = Process::findOrFail($processId);

        return (new ProcessVariableMigrator())->apply($process, $variables);
    }

    private function loadBpmnTemplate(string $template): string
    {
        $file = match ($template) {
            'OnlyStartElement', 'start_only' => 'OnlyStartElement.bpmn',
            'Collaboration' => 'Collaboration.bpmn',
            default => 'SingleTask.bpmn',
        };

        $path = database_path('processes/templates/' . $file);
        if (!is_readable($path)) {
            return Process::getProcessTemplate('SingleTask.bpmn');
        }

        return file_get_contents($path);
    }

    private function defaultUserTaskElementId(string $processId): ?string
    {
        $process = Process::findOrFail($processId);
        $xpath = \ProcessMaker\Mcp\Migration\MigrationBpmn::createXPath($process->getDefinitions(true));
        $task = $xpath->query('//bpmn:userTask[@id] | //bpmn:task[@id]')->item(0);

        return $task instanceof \DOMElement ? $task->getAttribute('id') : null;
    }
}
