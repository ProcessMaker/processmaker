<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use ProcessMaker\Exception\MissingBpmnReferenceException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ProcessBpmnReferenceService
{
    public function resolveNamedReferences(string $bpmn): string
    {
        $bpmn = $this->replaceNamedReferences(
            $bpmn,
            'screen',
            fn (array $names) => Screen::query()
                ->whereIn('title', $names)
                ->pluck('id', 'title')
                ->all()
        );

        $bpmn = $this->replaceNamedReferences(
            $bpmn,
            'script',
            fn (array $names) => Script::query()
                ->whereIn('title', $names)
                ->pluck('id', 'title')
                ->all()
        );

        $bpmn = $this->resolveWebEntryScreenReferences($bpmn);

        return $this->resolveNamedProcessCalls($bpmn);
    }

    public function restoreNamedReferences(string $bpmn): string
    {
        $bpmn = $this->replaceIdReferences(
            $bpmn,
            'screen',
            fn (array $ids) => Screen::query()
                ->whereIn('id', $ids)
                ->pluck('title', 'id')
                ->all()
        );

        $bpmn = $this->replaceIdReferences(
            $bpmn,
            'script',
            fn (array $ids) => Script::query()
                ->whereIn('id', $ids)
                ->pluck('title', 'id')
                ->all()
        );

        $bpmn = $this->restoreWebEntryScreenReferences($bpmn);

        return $this->restoreNamedProcessCalls($bpmn);
    }

    private function resolveWebEntryScreenReferences(string $bpmn): string
    {
        $names = [];

        $this->transformWebEntryConfigs($bpmn, function (array $config) use (&$names): array {
            foreach (['screen_id', 'completed_screen_id'] as $field) {
                $name = $this->screenReferenceName($config['web_entry'][$field] ?? null);
                if ($name !== null) {
                    $names[] = $name;
                }
            }

            return $config;
        });

        $names = array_values(array_unique($names));
        if ($names === []) {
            return $bpmn;
        }

        $idsByName = Screen::query()
            ->whereIn('title', $names)
            ->pluck('id', 'title')
            ->all();
        $missing = array_values(array_diff($names, array_keys($idsByName)));
        if ($missing !== []) {
            throw new MissingBpmnReferenceException(
                'Missing screen reference(s): ' . implode(', ', $missing)
            );
        }

        return $this->transformWebEntryConfigs($bpmn, function (array $config) use ($idsByName): array {
            foreach (['screen_id', 'completed_screen_id'] as $field) {
                $name = $this->screenReferenceName($config['web_entry'][$field] ?? null);
                if ($name !== null) {
                    $config['web_entry'][$field] = $idsByName[$name];
                }
            }

            return $config;
        });
    }

    private function restoreWebEntryScreenReferences(string $bpmn): string
    {
        $ids = [];

        $this->transformWebEntryConfigs($bpmn, function (array $config) use (&$ids): array {
            foreach (['screen_id', 'completed_screen_id'] as $field) {
                $id = $this->screenId($config['web_entry'][$field] ?? null);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }

            return $config;
        });

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return $bpmn;
        }

        $titlesById = Screen::query()
            ->whereIn('id', $ids)
            ->pluck('title', 'id')
            ->all();

        return $this->transformWebEntryConfigs($bpmn, function (array $config) use ($titlesById): array {
            foreach (['screen_id', 'completed_screen_id'] as $field) {
                $id = $this->screenId($config['web_entry'][$field] ?? null);
                if ($id !== null && isset($titlesById[$id])) {
                    $config['web_entry'][$field] = '${screen:' . $titlesById[$id] . '}';
                }
            }

            return $config;
        });
    }

    private function transformWebEntryConfigs(string $bpmn, callable $transform): string
    {
        $pattern = '/(pm:config\s*=\s*)(["\'])(.*?)\2/s';

        return preg_replace_callback($pattern, static function (array $match) use ($transform): string {
            $json = html_entity_decode($match[3], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $config = json_decode($json, true);
            if (!is_array($config) || !is_array($config['web_entry'] ?? null)) {
                return $match[0];
            }

            $transformed = $transform($config);
            if ($transformed === $config) {
                return $match[0];
            }

            $encoded = htmlspecialchars(
                json_encode($transformed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );

            return $match[1] . $match[2] . $encoded . $match[2];
        }, $bpmn) ?? $bpmn;
    }

    private function screenReferenceName(mixed $value): ?string
    {
        if (is_string($value) && preg_match('/^\$\{screen:([^}]+)\}$/', $value, $match)) {
            return $match[1];
        }

        return null;
    }

    private function screenId(mixed $value): ?int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        return null;
    }

    private function replaceNamedReferences(
        string $bpmn,
        string $type,
        callable $loadIds
    ): string {
        $pattern = '/(pm:' . $type . 'Ref\s*=\s*)(["\'])\$\{' . $type . ':([^}]+)\}\2/';
        preg_match_all($pattern, $bpmn, $matches);
        $names = array_values(array_unique($matches[3] ?? []));

        if ($names === []) {
            return $bpmn;
        }

        $idsByName = $loadIds($names);
        $missing = array_values(array_diff($names, array_keys($idsByName)));
        if ($missing !== []) {
            throw new MissingBpmnReferenceException(
                'Missing ' . $type . ' reference(s): ' . implode(', ', $missing)
            );
        }

        return preg_replace_callback(
            $pattern,
            static fn (array $match): string => $match[1] . $match[2]
                . $idsByName[$match[3]] . $match[2],
            $bpmn
        );
    }

    private function replaceIdReferences(
        string $bpmn,
        string $type,
        callable $loadNames
    ): string {
        $pattern = '/(pm:' . $type . 'Ref\s*=\s*)(["\'])(\d+)\2/';
        preg_match_all($pattern, $bpmn, $matches);
        $ids = array_values(array_unique($matches[3] ?? []));

        if ($ids === []) {
            return $bpmn;
        }

        $namesById = $loadNames($ids);

        return preg_replace_callback(
            $pattern,
            static function (array $match) use ($namesById, $type): string {
                $name = $namesById[$match[3]] ?? null;
                if ($name === null) {
                    return $match[0];
                }

                return $match[1] . $match[2] . '${' . $type . ':' . $name . '}' . $match[2];
            },
            $bpmn
        );
    }

    private function resolveNamedProcessCalls(string $bpmn): string
    {
        $pattern = '/(calledElement\s*=\s*)(["\'])\$\{process:([^}]+)\}\2/';
        preg_match_all($pattern, $bpmn, $matches);
        $names = array_values(array_unique($matches[3] ?? []));
        if ($names === []) {
            return $bpmn;
        }

        $processes = Process::query()->whereIn('name', $names)->get()->keyBy('name');
        $missing = array_values(array_diff($names, $processes->keys()->all()));
        if ($missing !== []) {
            throw new MissingBpmnReferenceException(
                'Missing process reference(s): ' . implode(', ', $missing)
            );
        }

        $calledElementByName = [];
        foreach ($processes as $name => $process) {
            $calledElementByName[$name] = $this->bpmnProcessXmlId((string) $process->bpmn)
                . '-' . $process->id;
        }

        return preg_replace_callback(
            $pattern,
            static fn (array $match): string => $match[1] . $match[2]
                . $calledElementByName[$match[3]] . $match[2],
            $bpmn
        );
    }

    private function restoreNamedProcessCalls(string $bpmn): string
    {
        $pattern = '/(calledElement\s*=\s*)(["\'])([^"\']+)-(\d+)\2/';
        preg_match_all($pattern, $bpmn, $matches);
        $ids = array_values(array_unique($matches[4] ?? []));
        if ($ids === []) {
            return $bpmn;
        }

        $namesById = Process::query()->whereIn('id', $ids)->pluck('name', 'id')->all();

        return preg_replace_callback(
            $pattern,
            static function (array $match) use ($namesById): string {
                $name = $namesById[(int) $match[4]] ?? $namesById[$match[4]] ?? null;
                if ($name === null) {
                    return $match[0];
                }

                return $match[1] . $match[2] . '${process:' . $name . '}' . $match[2];
            },
            $bpmn
        );
    }

    private function bpmnProcessXmlId(string $childBpmn): string
    {
        if ($childBpmn !== '' && preg_match(
            '/<bpmn:process\b[^>]*\bid\s*=\s*["\']([^"\']+)["\']/',
            $childBpmn,
            $match
        )) {
            return $match[1];
        }

        return 'ProcessId';
    }
}
