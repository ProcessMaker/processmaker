<?php

namespace ProcessMaker\ImportExport;

use Illuminate\Support\Facades\Schema;
use ProcessMaker\Exception\UnsupportedImportAssetException;

class UnsupportedAssetValidator
{
    private array $rules;

    public function __construct()
    {
        $this->rules = array_map(function (array $rule) {
            return [
                'name' => $rule['name'],
                'classes' => array_map(self::normalizeClass(...), $rule['classes'] ?? []),
                'namespace_prefixes' => array_map(
                    fn (string $prefix) => self::normalizeClass($prefix) . '\\',
                    $rule['namespace_prefixes'] ?? []
                ),
                'available_when' => $rule['available_when'] ?? null,
            ];
        }, config('import-export.unsupported_assets', []));
    }

    public function validate(array $assets): void
    {
        $availability = [];
        foreach ($assets as $asset) {
            $this->validateReference($asset, $availability);
            foreach ($asset['dependents'] ?? [] as $dependent) {
                $this->validateReference($dependent, $availability);
            }
        }
    }

    private function validateReference(array $reference, array &$availability): void
    {
        foreach (['model', 'exporter', 'modelClass', 'exporterClass'] as $key) {
            if (!isset($reference[$key])) {
                continue;
            }
            $class = self::normalizeClass($reference[$key]);
            foreach ($this->rules as $index => $rule) {
                if (!$this->matchesRule($class, $rule)) {
                    continue;
                }

                $requirements = $rule['available_when'];
                $availability[$index] ??= $requirements !== null && $this->isAvailable($requirements);
                if ($availability[$index] && class_exists($reference[$key])) {
                    continue;
                }

                throw new UnsupportedImportAssetException(
                    __('This process package contains :resource resources, which are not supported for import. Remove the :resource resources and references from the source process, export it again, and retry the import.', [
                        'resource' => __($rule['name']),
                    ])
                );
            }
        }
    }

    private function matchesRule(string $class, array $rule): bool
    {
        if (in_array($class, $rule['classes'], true)) {
            return true;
        }

        foreach ($rule['namespace_prefixes'] as $prefix) {
            if (str_starts_with($class, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAvailable(array $requirements): bool
    {
        foreach ($requirements['classes'] as $class) {
            if (!class_exists($class)) {
                return false;
            }
        }

        foreach ($requirements['tables'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private static function normalizeClass(string $class): string
    {
        return strtolower(trim($class, '\\'));
    }
}
