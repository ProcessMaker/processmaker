<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

use ProcessMaker\Mcp\Migration\MigrationBpmn;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Providers\WorkflowServiceProvider;

class Reader
{
    /**
     * @return array{processes: array<int, array<string, mixed>>, total: int}
     */
    public function listProcesses(int $start = 0, int $limit = 25, ?string $filterName = null, ?string $status = 'ACTIVE'): array
    {
        $query = Process::nonSystem()->orderBy('name');

        $status = strtoupper($status ?? 'ACTIVE');

        if ($status === 'ARCHIVED') {
            $query = Process::archived()->orderBy('name');
        } elseif ($status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($filterName !== null && $filterName !== '') {
            $query->filter($filterName);
        }

        $total = (clone $query)->count();
        $processes = $query->skip($start)->take($limit)->get(['id', 'name', 'description', 'status', 'process_category_id', 'updated_at']);

        return [
            'processes' => $processes->map(fn (Process $process): array => [
                'id' => $process->id,
                'name' => $process->name,
                'description' => $process->description,
                'status' => $process->status,
                'process_category_id' => $process->process_category_id,
                'updated_at' => $process->updated_at?->toIso8601String(),
            ])->all(),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProcess(string $processId): array
    {
        $process = Process::findOrFail($processId);
        $linked = $this->extractLinkedAssets($process);

        return [
            'id' => $process->id,
            'name' => $process->name,
            'description' => $process->description,
            'status' => $process->status,
            'process_category_id' => $process->process_category_id,
            'properties' => is_array($process->properties) ? $process->properties : [],
            'linked_assets' => $linked,
            'updated_at' => $process->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProcessInventory(string $processId): array
    {
        $process = Process::findOrFail($processId);
        $linked = $this->extractLinkedAssets($process);
        $properties = is_array($process->properties) ? $process->properties : [];

        return [
            'process_id' => $process->id,
            'name' => $process->name,
            'status' => $process->status,
            'counts' => [
                'tasks' => count($linked['tasks']),
                'script_tasks' => count($linked['script_tasks']),
                'gateways' => count($linked['gateways']),
                'linked_screens' => count($linked['screens']),
                'linked_scripts' => count($linked['scripts']),
                'variables' => count($properties['variables'] ?? []),
                'start_events' => count($linked['start_events']),
                'end_events' => count($linked['end_events']),
            ],
            'linked_assets' => $linked,
            'variables' => $properties['variables'] ?? [],
        ];
    }

    /**
     * @return array{process_id: string, bpmn: string}
     */
    public function getProcessBpmn(string $processId): array
    {
        $process = Process::findOrFail($processId);

        return [
            'process_id' => $process->id,
            'bpmn' => $process->bpmn,
        ];
    }

    /**
     * @return array{screens: array<int, array<string, mixed>>, total: int}
     */
    public function listScreens(int $start = 0, int $limit = 25, ?string $filter = null): array
    {
        $query = Screen::nonSystem()->orderBy('title');

        if ($filter !== null && $filter !== '') {
            $query->filter($filter);
        }

        $total = (clone $query)->count();

        return [
            'screens' => $query->skip($start)->take($limit)->get(['id', 'title', 'type', 'description', 'updated_at'])
                ->map(fn (Screen $screen): array => [
                    'id' => $screen->id,
                    'title' => $screen->title,
                    'type' => $screen->type,
                    'description' => $screen->description,
                ])->all(),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getScreen(string $screenId): array
    {
        $screen = Screen::findOrFail($screenId);

        return [
            'id' => $screen->id,
            'title' => $screen->title,
            'type' => $screen->type,
            'description' => $screen->description,
            'config' => $screen->config,
            'custom_css' => $screen->custom_css,
        ];
    }

    /**
     * @return array{scripts: array<int, array<string, mixed>>, total: int}
     */
    public function listScripts(int $start = 0, int $limit = 25, ?string $filter = null): array
    {
        $query = Script::nonSystem()->orderBy('title');

        if ($filter !== null && $filter !== '') {
            $query->filter($filter);
        }

        $total = (clone $query)->count();

        return [
            'scripts' => $query->skip($start)->take($limit)->get(['id', 'title', 'language', 'description', 'updated_at'])
                ->map(fn (Script $script): array => [
                    'id' => $script->id,
                    'title' => $script->title,
                    'language' => $script->language,
                    'description' => $script->description,
                ])->all(),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getScript(string $scriptId): array
    {
        $script = Script::findOrFail($scriptId);

        return [
            'id' => $script->id,
            'title' => $script->title,
            'language' => $script->language,
            'description' => $script->description,
            'code' => $script->code,
            'run_as_user_id' => $script->run_as_user_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractLinkedAssets(Process $process): array
    {
        $definitions = $process->getDefinitions(true);
        $xpath = MigrationBpmn::createXPath($definitions);
        $pmNs = WorkflowServiceProvider::PROCESS_MAKER_NS;

        $tasks = [];
        foreach ($xpath->query('//bpmn:task[@id] | //bpmn:userTask[@id]') as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $screenRef = $node->getAttributeNS($pmNs, 'screenRef');
            $assignment = $node->getAttributeNS($pmNs, 'assignment');

            $tasks[] = [
                'element_id' => $node->getAttribute('id'),
                'name' => $node->getAttribute('name'),
                'screen_id' => $screenRef !== '' ? $screenRef : null,
                'assignment' => $assignment !== '' ? $assignment : null,
            ];
        }

        $scriptTasks = [];
        foreach ($xpath->query('//bpmn:scriptTask[@id]') as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $scriptTasks[] = [
                'element_id' => $node->getAttribute('id'),
                'name' => $node->getAttribute('name'),
                'script_id' => $node->getAttributeNS($pmNs, 'scriptRef') ?: null,
            ];
        }

        $gateways = [];
        foreach ($xpath->query('//bpmn:exclusiveGateway[@id] | //bpmn:parallelGateway[@id] | //bpmn:inclusiveGateway[@id]') as $node) {
            if ($node instanceof \DOMElement) {
                $gateways[] = [
                    'element_id' => $node->getAttribute('id'),
                    'name' => $node->getAttribute('name'),
                    'type' => $node->localName,
                ];
            }
        }

        $screens = [];
        $scripts = [];
        foreach ($tasks as $task) {
            if ($task['screen_id'] !== null && !isset($screens[$task['screen_id']])) {
                $screens[$task['screen_id']] = [
                    'screen_id' => $task['screen_id'],
                    'linked_to_elements' => [],
                ];
            }
            if ($task['screen_id'] !== null) {
                $screens[$task['screen_id']]['linked_to_elements'][] = $task['element_id'];
            }
        }
        foreach ($scriptTasks as $scriptTask) {
            if ($scriptTask['script_id'] !== null && !isset($scripts[$scriptTask['script_id']])) {
                $scripts[$scriptTask['script_id']] = [
                    'script_id' => $scriptTask['script_id'],
                    'linked_to_elements' => [],
                ];
            }
            if ($scriptTask['script_id'] !== null) {
                $scripts[$scriptTask['script_id']]['linked_to_elements'][] = $scriptTask['element_id'];
            }
        }

        $startEvents = [];
        foreach ($xpath->query('//bpmn:startEvent[@id]') as $node) {
            if ($node instanceof \DOMElement) {
                $startEvents[] = ['element_id' => $node->getAttribute('id'), 'name' => $node->getAttribute('name')];
            }
        }

        $endEvents = [];
        foreach ($xpath->query('//bpmn:endEvent[@id]') as $node) {
            if ($node instanceof \DOMElement) {
                $endEvents[] = ['element_id' => $node->getAttribute('id'), 'name' => $node->getAttribute('name')];
            }
        }

        $orphanTasks = array_values(array_filter(
            $tasks,
            fn (array $task): bool => $task['screen_id'] === null
        ));

        return [
            'tasks' => $tasks,
            'script_tasks' => $scriptTasks,
            'gateways' => $gateways,
            'screens' => array_values($screens),
            'scripts' => array_values($scripts),
            'start_events' => $startEvents,
            'end_events' => $endEvents,
            'tasks_without_screen' => $orphanTasks,
        ];
    }
}
