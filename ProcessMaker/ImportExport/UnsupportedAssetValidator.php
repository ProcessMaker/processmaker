<?php

namespace ProcessMaker\ImportExport;

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
            ];
        }, config('import-export.unsupported_assets', []));
    }

    public function validate(array $assets): void
    {
        foreach ($assets as $asset) {
            $this->validateReference($asset);
            foreach ($asset['dependents'] ?? [] as $dependent) {
                $this->validateReference($dependent);
            }
        }
    }

    private function validateReference(array $reference): void
    {
        foreach (['model', 'exporter', 'modelClass', 'exporterClass'] as $key) {
            if (!isset($reference[$key])) {
                continue;
            }
            $class = self::normalizeClass($reference[$key]);
            foreach ($this->rules as $rule) {
                $matches = in_array($class, $rule['classes'], true);
                foreach ($rule['namespace_prefixes'] as $prefix) {
                    if (str_starts_with($class, $prefix)) {
                        $matches = true;
                        break;
                    }
                }
                if ($matches) {
                    throw new UnsupportedImportAssetException(
                        __('This process package contains :resource resources, which are not supported for import. Remove the :resource resources and references from the source process, export it again, and retry the import.', [
                            'resource' => __($rule['name']),
                        ])
                    );
                }
            }
        }
    }

    private static function normalizeClass(string $class): string
    {
        return strtolower(trim($class, '\\'));
    }
}
