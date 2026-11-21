<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Screens;

/**
 * Semantic checks for Screen Builder configs used by MCP screen tools.
 */
final class ScreenConfigValidator
{
    private const NATIVE_FIELD_COMPONENTS = [
        'FormInput',
        'FormSelectList',
        'FormSelect',
        'FormTextArea',
        'FormDatePicker',
        'FormCheckbox',
        'FormRadioButtonGroup',
        'FormButton',
        'FormHtmlViewer',
    ];

    /**
     * @param array<int, mixed>|null $config
     * @return array{ok: bool, errors: list<array{path: ?string, code: string, message: string}>, warnings: list<array{path: ?string, code: string, message: string}>}
     */
    public function validate(?array $config, ?string $type = null): array
    {
        $errors = [];
        $warnings = [];

        if ($config === null) {
            return $this->result($errors, $warnings);
        }

        $hasVueEditor = false;
        $hasNativeFields = false;

        foreach ($config as $pageIndex => $page) {
            if (!is_array($page)) {
                $errors[] = $this->issue("config.{$pageIndex}", 'invalid_page', 'Each config page must be an object.');
                continue;
            }

            $items = $page['items'] ?? [];
            if (!is_array($items)) {
                $errors[] = $this->issue("config.{$pageIndex}.items", 'invalid_items', 'Page items must be an array.');
                continue;
            }

            $this->walkItems(
                $items,
                "config.{$pageIndex}.items",
                $errors,
                $warnings,
                $hasVueEditor,
                $hasNativeFields,
            );
        }

        if ($type === 'FORM' && $hasNativeFields && !$hasVueEditor) {
            $warnings[] = $this->issue(
                null,
                'prefer_vue_component_editor',
                'FORM screens should use VueComponentRenderTemplate as the main UI (see the pm4-screen skill). Native FormSelectList/FormInput layouts are fragile and often miss required options.',
            );
        }

        return $this->result($errors, $warnings);
    }

    /**
     * @param list<mixed> $items
     * @param list<array{path: ?string, code: string, message: string}> $errors
     * @param list<array{path: ?string, code: string, message: string}> $warnings
     */
    private function walkItems(
        array $items,
        string $path,
        array &$errors,
        array &$warnings,
        bool &$hasVueEditor,
        bool &$hasNativeFields,
    ): void {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemPath = "{$path}.{$index}";
            $component = (string) ($item['component'] ?? '');

            if ($component === 'VueComponentRenderTemplate') {
                $hasVueEditor = true;
                $this->validateVueEditor($item, $itemPath, $errors, $warnings);
            } elseif (in_array($component, self::NATIVE_FIELD_COMPONENTS, true)) {
                $hasNativeFields = true;
            }

            if ($component === 'FormSelectList') {
                $this->validateSelectList($item, $itemPath, $errors);
            }

            if (!empty($item['container']) && isset($item['items']) && is_array($item['items'])) {
                foreach ($item['items'] as $columnIndex => $columnItems) {
                    if (!is_array($columnItems)) {
                        continue;
                    }
                    $this->walkItems(
                        $columnItems,
                        "{$itemPath}.items.{$columnIndex}",
                        $errors,
                        $warnings,
                        $hasVueEditor,
                        $hasNativeFields,
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param list<array{path: ?string, code: string, message: string}> $errors
     * @param list<array{path: ?string, code: string, message: string}> $warnings
     */
    private function validateVueEditor(array $item, string $path, array &$errors, array &$warnings): void
    {
        $config = is_array($item['config'] ?? null) ? $item['config'] : [];
        $contentVue = trim((string) ($config['contentVue'] ?? ''));
        if ($contentVue === '') {
            $errors[] = $this->issue("{$path}.config.contentVue", 'vue_content_missing', 'VueComponentRenderTemplate requires non-empty contentVue.');
        }

        $components = $config['components'] ?? null;
        if (!is_array($components) || $components === []) {
            $errors[] = $this->issue(
                "{$path}.config.components",
                'vue_components_missing',
                'VueComponentRenderTemplate requires config.components with {name,key} entries for mounted tags.',
            );
        } else {
            foreach ($components as $compIndex => $entry) {
                if (!is_array($entry) || trim((string) ($entry['name'] ?? '')) === '' || trim((string) ($entry['key'] ?? '')) === '') {
                    $errors[] = $this->issue(
                        "{$path}.config.components.{$compIndex}",
                        'vue_component_entry_invalid',
                        'Each components entry must include non-empty name and key.',
                    );
                }
            }
        }

        $handlerVue = trim((string) ($config['handlerVue'] ?? ''));
        if ($handlerVue !== '' && !str_starts_with($handlerVue, 'return')) {
            $warnings[] = $this->issue(
                "{$path}.config.handlerVue",
                'vue_handler_missing_return',
                'handlerVue should start with return { ... }.',
            );
        }

        if (($config['ignoreMustache'] ?? null) !== true) {
            $warnings[] = $this->issue(
                "{$path}.config.ignoreMustache",
                'vue_ignore_mustache',
                'Set ignoreMustache to true for VueComponentRenderTemplate.',
            );
        }
    }

    /**
     * @param array<string, mixed> $item
     * @param list<array{path: ?string, code: string, message: string}> $errors
     */
    private function validateSelectList(array $item, string $path, array &$errors): void
    {
        $config = is_array($item['config'] ?? null) ? $item['config'] : [];
        $name = (string) ($config['name'] ?? 'unnamed');
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $dataSource = (string) ($options['dataSource'] ?? 'provideData');

        if ($dataSource !== 'provideData') {
            return;
        }

        $optionsList = $options['optionsList'] ?? null;
        if (is_array($optionsList) && $optionsList !== []) {
            return;
        }

        $fromJson = $this->optionsFromJsonData($options['jsonData'] ?? null);
        if ($fromJson !== []) {
            $errors[] = $this->issue(
                "{$path}.config.options.optionsList",
                'select_options_list_missing',
                "FormSelectList \"{$name}\" has jsonData but empty/missing optionsList. Screen Builder needs optionsList (array of {value,content}) or the dropdown renders empty and required selects block submit.",
            );

            return;
        }

        $errors[] = $this->issue(
            "{$path}.config.options",
            'select_options_empty',
            "FormSelectList \"{$name}\" with dataSource provideData has no options. Provide optionsList with at least one {value,content} entry.",
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function optionsFromJsonData(mixed $jsonData): array
    {
        if (!is_string($jsonData) || trim($jsonData) === '') {
            return [];
        }

        $decoded = json_decode($jsonData, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    /**
     * @return array{path: ?string, code: string, message: string}
     */
    private function issue(?string $path, string $code, string $message): array
    {
        return [
            'path' => $path,
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * @param list<array{path: ?string, code: string, message: string}> $errors
     * @param list<array{path: ?string, code: string, message: string}> $warnings
     * @return array{ok: bool, errors: list<array{path: ?string, code: string, message: string}>, warnings: list<array{path: ?string, code: string, message: string}>}
     */
    private function result(array $errors, array $warnings): array
    {
        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
