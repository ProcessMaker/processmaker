<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

class MigrationGapReport
{
    /**
     * @param  array<string, mixed>  $bundle
     * @param  array<string, mixed>  $result
     * @return array{ready: bool, gaps: array<int, string>, score: int}
     */
    public function build(array $bundle, array $result): array
    {
        $gaps = [];

        if (($bundle['variables'] ?? []) !== [] && ($result['applied_variables'] ?? []) === []) {
            $gaps[] = 'Process variables were not stored.';
        }

        foreach ($bundle['steps'] ?? [] as $step) {
            if (!is_array($step) || ($step['step_type'] ?? null) !== 'DYNAFORM') {
                continue;
            }

            $dynUid = $step['dyn_uid'] ?? null;
            $linked = false;
            foreach ($result['linked_screens'] ?? [] as $link) {
                if (is_array($link) && ($link['dyn_uid'] ?? null) === $dynUid) {
                    $linked = true;
                    break;
                }
            }

            if (!$linked) {
                $gaps[] = 'Dynaform step ' . ($dynUid ?? 'unknown') . ' is not linked to a BPMN task.';
            }
        }

        foreach ($bundle['steps'] ?? [] as $step) {
            if (!is_array($step) || ($step['triggers'] ?? []) === []) {
                continue;
            }

            $tasUid = $step['tas_uid'] ?? 'unknown';
            $applied = count(array_filter(
                $result['applied_step_triggers'] ?? [],
                fn ($entry) => is_array($entry) && ($entry['tas_uid'] ?? null) === $tasUid
            ));

            if ($applied < count($step['triggers'])) {
                $gaps[] = 'Step triggers on task ' . $tasUid . ' are incomplete.';
            }
        }

        if (($bundle['web_entries'] ?? []) !== [] && ($result['applied_web_entries'] ?? []) === []) {
            $gaps[] = 'Web entries were not configured.';
        }

        if (($bundle['document_steps'] ?? []) !== []
            && !in_array('document_steps', $result['applied_documents'] ?? [], true)) {
            $gaps[] = 'Input/output document steps were not stored.';
        }

        if (($result['validation']['valid'] ?? false) !== true) {
            $gaps[] = 'Process BPMN validation failed.';
        }

        foreach ($result['warnings'] ?? [] as $warning) {
            if (is_string($warning) && str_contains($warning, 'PM3 API')) {
                $gaps[] = $warning;
            }
        }

        $total = max(1, count($bundle['tasks'] ?? []) + count($bundle['triggers'] ?? []) + count($bundle['variables'] ?? []));
        $score = (int) round((1 - count($gaps) / $total) * 100);

        return [
            'ready' => $gaps === [],
            'gaps' => array_values(array_unique($gaps)),
            'score' => max(0, min(100, $score)),
        ];
    }
}
