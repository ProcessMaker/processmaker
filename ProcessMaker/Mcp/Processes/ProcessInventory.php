<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Processes;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;

/**
 * Read-only map of a process: its activities with the assets they reference
 * (ids resolved to titles), the screens with their field names, the scripts, the
 * gateways with the variables their conditions read, and where those variables
 * are written by a screen.
 *
 * It replaces about 75 API reads and a scripted analysis for a health review.
 * It only reads: scripts, data connectors and call activities can also write
 * variables, and their output is not analysed here.
 */
class ProcessInventory
{
    // XML namespace identifiers, not network URLs: they must match the BPMN document byte for byte, so they cannot use https.
    private const BPMN_NS = 'http://www.omg.org/spec/BPMN/20100524/MODEL'; // NOSONAR

    private const PM_NS = 'http://processmaker.com/BPMN/2.0/Schema.xsd'; // NOSONAR

    private const ACTIVITIES = [
        'task', 'userTask', 'manualTask', 'scriptTask', 'serviceTask', 'businessRuleTask',
        'callActivity', 'subProcess',
    ];

    private const GATEWAYS = ['exclusiveGateway', 'inclusiveGateway', 'parallelGateway', 'eventBasedGateway'];

    /** Controls that show or lay out content and do not hold a variable. */
    private const NON_FIELD_COMPONENTS = [
        'FormButton', 'FormText', 'FormHtmlViewer', 'FormHtmlEditor', 'FormImage', 'FormNestedScreen',
        'FormCollectionViewControl', 'PageNavigation', 'FormMultiColumn', 'FormLoop',
    ];

    private const IGNORED_WORDS = ['true', 'false', 'null', 'YES', 'NO'];

    /**
     * @return array<string, mixed>
     */
    public function build(Process $process, string $alternative, ?string $bpmn, ?User $viewer = null): array
    {
        $dom = new DOMDocument();
        if ($bpmn === null || $bpmn === '' || !@$dom->loadXML($bpmn)) {
            return $this->empty($process, $alternative);
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('b', self::BPMN_NS);

        $activities = $this->activities($xpath);
        $screens = $this->screens($activities);
        $scripts = $this->scripts($activities, $viewer);
        $gateways = $this->gateways($xpath);

        return [
            'process' => [
                'id' => $process->id,
                'name' => $process->name,
                'status' => $process->status,
                'alternative' => $alternative,
            ],
            'counts' => [
                'activities' => count($activities),
                'gateways' => count($gateways),
                'sequence_flows' => $xpath->query('//b:sequenceFlow')->length,
                'screens' => count($screens),
                'scripts' => count($scripts),
            ],
            'activities' => $this->withTitles($activities, $screens, $scripts),
            'screens' => array_values($screens),
            'scripts' => array_values($scripts),
            'gateways' => $gateways,
            'variables' => $this->variables($gateways, $screens),
            'warnings' => $this->warnings($gateways, $screens),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(Process $process, string $alternative): array
    {
        return [
            'process' => ['id' => $process->id, 'name' => $process->name, 'status' => $process->status, 'alternative' => $alternative],
            'counts' => ['activities' => 0, 'gateways' => 0, 'sequence_flows' => 0, 'screens' => 0, 'scripts' => 0],
            'activities' => [],
            'screens' => [],
            'scripts' => [],
            'gateways' => [],
            'variables' => [],
            'warnings' => ['The process has no readable BPMN.'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function activities(DOMXPath $xpath): array
    {
        $activities = [];
        foreach (self::ACTIVITIES as $type) {
            foreach ($xpath->query('//b:' . $type) as $node) {
                /** @var DOMElement $node */
                $screenId = $this->integerAttribute($node, 'screenRef');
                $scriptId = $this->integerAttribute($node, 'scriptRef');
                $activities[$node->getAttribute('id')] = [
                    'id' => $node->getAttribute('id'),
                    'type' => $type,
                    'name' => $node->getAttribute('name'),
                    'screen_id' => $screenId,
                    'script_id' => $scriptId,
                    'assignment' => $node->getAttributeNS(self::PM_NS, 'assignment') ?: null,
                    'incoming' => $this->childCount($node, 'incoming'),
                    'outgoing' => $this->childCount($node, 'outgoing'),
                ];
            }
        }

        return $activities;
    }

    /**
     * @param array<string, array<string, mixed>> $activities
     * @return array<string, array<string, mixed>>
     */
    private function screens(array $activities): array
    {
        $referencedBy = [];
        foreach ($activities as $activity) {
            if ($activity['screen_id'] !== null) {
                $referencedBy[$activity['screen_id']][] = $activity['id'];
            }
        }
        if ($referencedBy === []) {
            return [];
        }

        $screens = [];
        foreach (Screen::query()->whereIn('id', array_keys($referencedBy))->get(['id', 'title', 'type', 'config']) as $screen) {
            $controls = $this->controls((array) $screen->config);
            $screens[$screen->id] = [
                'id' => $screen->id,
                'title' => $screen->title,
                'type' => $screen->type,
                'controls' => count($controls),
                'fields' => array_values(array_unique(array_filter(array_map(
                    static fn (array $control): ?string => $control['field'],
                    $controls,
                )))),
                'referenced_by' => $referencedBy[$screen->id],
            ];
        }
        foreach (array_diff(array_keys($referencedBy), array_keys($screens)) as $missingId) {
            $screens[$missingId] = [
                'id' => $missingId, 'title' => null, 'type' => null, 'controls' => 0, 'fields' => [],
                'referenced_by' => $referencedBy[$missingId],
            ];
        }
        ksort($screens);

        return $screens;
    }

    /**
     * @param array<string, array<string, mixed>> $activities
     * @return array<int, array<string, mixed>>
     */
    private function scripts(array $activities, ?User $viewer): array
    {
        // Script metadata is gated by view-scripts on the scripts API. Without it
        // (or without a known viewer), do not resolve or confirm any script id.
        if ($viewer === null || !$viewer->can('view-scripts')) {
            return [];
        }

        $referencedBy = [];
        foreach ($activities as $activity) {
            if ($activity['script_id'] !== null) {
                $referencedBy[$activity['script_id']][] = $activity['id'];
            }
        }
        if ($referencedBy === []) {
            return [];
        }

        $scripts = [];
        foreach (Script::query()->whereIn('id', array_keys($referencedBy))->get(['id', 'title', 'language', 'script_executor_id']) as $script) {
            $scripts[$script->id] = [
                'id' => $script->id,
                'title' => $script->title,
                'language' => $script->language,
                'script_executor_id' => $script->script_executor_id,
                'referenced_by' => $referencedBy[$script->id],
            ];
        }
        foreach (array_diff(array_keys($referencedBy), array_keys($scripts)) as $missingId) {
            $scripts[$missingId] = [
                'id' => $missingId, 'title' => null, 'language' => null, 'script_executor_id' => null,
                'referenced_by' => $referencedBy[$missingId],
            ];
        }
        ksort($scripts);

        return $scripts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gateways(DOMXPath $xpath): array
    {
        $flows = $this->flowsBySource($xpath);
        $gateways = [];
        foreach (self::GATEWAYS as $type) {
            foreach ($xpath->query('//b:' . $type) as $node) {
                /** @var DOMElement $node */
                $id = $node->getAttribute('id');
                $outgoing = $flows[$id] ?? [];
                $gateways[] = [
                    'id' => $id,
                    'type' => $type,
                    'name' => $node->getAttribute('name'),
                    'default_flow' => $node->getAttribute('default') ?: null,
                    'incoming' => $this->childCount($node, 'incoming'),
                    'flows' => $outgoing,
                    'variables' => $this->conditionVariables($outgoing),
                ];
            }
        }

        return $gateways;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function flowsBySource(DOMXPath $xpath): array
    {
        $flows = [];
        foreach ($xpath->query('//b:sequenceFlow') as $flow) {
            /** @var DOMElement $flow */
            $condition = $xpath->query('b:conditionExpression', $flow)->item(0);
            $flows[$flow->getAttribute('sourceRef')][] = [
                'id' => $flow->getAttribute('id'),
                'target' => $flow->getAttribute('targetRef'),
                'condition' => $condition === null ? null : trim($condition->textContent),
            ];
        }

        return $flows;
    }

    /**
     * @param array<int, array<string, mixed>> $flows
     * @return array<int, string>
     */
    private function conditionVariables(array $flows): array
    {
        $variables = [];
        foreach ($flows as $flow) {
            $condition = (string) ($flow['condition'] ?? '');
            preg_match_all('/\b(?:data\.)?([A-Za-z_]\w*)\b(?:\[[^\]]*\])?\s*(?:==|!=|<=|>=|<|>)/', $condition, $matches);
            foreach ($matches[1] as $name) {
                if (!in_array($name, self::IGNORED_WORDS, true)) {
                    $variables[$name] = true;
                }
            }
        }

        return array_keys($variables);
    }

    /**
     * @param array<int, mixed> $config
     * @return array<int, array{component: string, field: ?string}>
     */
    private function controls(array $config): array
    {
        $controls = [];
        $walk = function (mixed $node) use (&$walk, &$controls): void {
            if (!is_array($node)) {
                return;
            }
            if (isset($node['component']) && is_string($node['component']) && isset($node['config'])) {
                $name = $node['config']['name'] ?? null;
                $isField = !in_array($node['component'], self::NON_FIELD_COMPONENTS, true) && is_string($name) && $name !== '';
                $controls[] = ['component' => $node['component'], 'field' => $isField ? $name : null];
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($config);

        return $controls;
    }

    /**
     * Only the variables a gateway reads: they decide the route, so their writers matter.
     * Every screen's own fields are listed under `screens`.
     *
     * @param array<int, array<string, mixed>> $gateways
     * @param array<string, array<string, mixed>> $screens
     * @return array<string, array{written_by_screens: array<int, int>, read_by_gateways: array<int, string>}>
     */
    private function variables(array $gateways, array $screens): array
    {
        $variables = [];
        foreach ($gateways as $gateway) {
            foreach ($gateway['variables'] as $name) {
                $variables[$name]['read_by_gateways'][] = $gateway['id'];
            }
        }
        ksort($variables);

        foreach ($variables as $name => $entry) {
            $variables[$name] = [
                'written_by_screens' => array_values(array_map(
                    static fn (array $screen): int => $screen['id'],
                    array_filter($screens, static fn (array $screen): bool => in_array($name, $screen['fields'], true)),
                )),
                'read_by_gateways' => $entry['read_by_gateways'],
            ];
        }

        return $variables;
    }

    /**
     * @param array<int, array<string, mixed>> $gateways
     * @param array<string, array<string, mixed>> $screens
     * @return array<int, string>
     */
    private function warnings(array $gateways, array $screens): array
    {
        $written = [];
        foreach ($screens as $screen) {
            foreach ($screen['fields'] as $field) {
                $written[$field] = true;
            }
        }

        $warnings = [];
        foreach ($gateways as $gateway) {
            $unwritten = array_values(array_filter($gateway['variables'], static fn (string $name): bool => !isset($written[$name])));
            if ($unwritten !== []) {
                $warnings[] = sprintf(
                    'Gateway %s reads %s, but no screen of this process has a field with that name. '
                    . 'Scripts, data connectors and call activities may still set it; an undefined variable in a '
                    . 'gateway condition puts the request in ERROR.',
                    $gateway['id'],
                    implode(', ', $unwritten),
                );
            }
        }

        return $warnings;
    }

    private function integerAttribute(DOMElement $node, string $name): ?int
    {
        $value = $node->getAttributeNS(self::PM_NS, $name);

        return ctype_digit($value) && $value !== '' ? (int) $value : null;
    }

    private function childCount(DOMElement $node, string $name): int
    {
        $count = 0;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name && $child->namespaceURI === self::BPMN_NS) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, array<string, mixed>> $activities
     * @param array<int, array<string, mixed>> $screens
     * @param array<int, array<string, mixed>> $scripts
     * @return array<int, array<string, mixed>>
     */
    private function withTitles(array $activities, array $screens, array $scripts): array
    {
        return array_values(array_map(static function (array $activity) use ($screens, $scripts): array {
            $activity['screen_title'] = $screens[$activity['screen_id']]['title'] ?? null;
            $activity['script_title'] = $scripts[$activity['script_id']]['title'] ?? null;

            return $activity;
        }, $activities));
    }
}
