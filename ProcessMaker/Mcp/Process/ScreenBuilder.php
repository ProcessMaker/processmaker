<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

class ScreenBuilder
{
    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function buildFormConfig(array $fields, ?string $pageTitle = null, bool $includeSubmit = true): array
    {
        $items = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $built = $this->buildField($field);
            if ($built !== null) {
                $items[] = $built;
            }
        }

        if ($includeSubmit) {
            $items[] = $this->buildSubmitButton();
        }

        return [[
            'name' => $pageTitle !== null && $pageTitle !== '' ? $pageTitle : 'Form',
            'computed' => [],
            'items' => $items,
        ]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $config
     * @return array{valid: bool, issues: array<int, array<string, mixed>>}
     */
    public function validateScreenConfig(array $config): array
    {
        $issues = [];
        $pages = $config;

        if ($pages === []) {
            return [
                'valid' => false,
                'issues' => [[
                    'code' => 'empty_config',
                    'severity' => 'error',
                    'message' => 'Screen config is empty.',
                ]],
            ];
        }

        $missingTemplates = ScreenControlCatalog::missingComponents();
        foreach ($missingTemplates as $component) {
            $issues[] = [
                'code' => 'missing_control_template',
                'severity' => 'error',
                'message' => 'MCP has no PM4 control template for ' . $component . '. Run get_mcp_capabilities after PM4 upgrade.',
            ];
        }

        $hasSubmit = false;
        $inputCount = 0;

        foreach ($pages as $pageIndex => $page) {
            if (!is_array($page)) {
                continue;
            }

            foreach ($page['items'] ?? [] as $itemIndex => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $component = (string) ($item['component'] ?? '');
                $itemConfig = is_array($item['config'] ?? null) ? $item['config'] : [];

                if ($component === 'FormButton' && ($itemConfig['event'] ?? null) === 'submit') {
                    $hasSubmit = true;
                }

                if (in_array($component, ['FormInput', 'FormTextArea', 'FormSelect', 'FormSelectList', 'FormCheckbox', 'FormDatePicker'], true)) {
                    $inputCount++;
                    $name = (string) ($itemConfig['name'] ?? '');

                    if ($name === '') {
                        $issues[] = [
                            'code' => 'field_missing_name',
                            'severity' => 'error',
                            'message' => 'Input field is missing config.name.',
                            'path' => "config.{$pageIndex}.items.{$itemIndex}",
                        ];
                    }

                    if (!is_array($item['inspector'] ?? null) || $item['inspector'] === []) {
                        $issues[] = [
                            'code' => 'field_missing_inspector',
                            'severity' => 'error',
                            'message' => 'Field "' . ($name ?: $component) . '" is missing inspector metadata.',
                            'path' => "config.{$pageIndex}.items.{$itemIndex}",
                        ];
                    }

                    if ($component === 'FormSelect' && empty($itemConfig['options'])) {
                        $issues[] = [
                            'code' => 'select_missing_options',
                            'severity' => 'error',
                            'message' => 'Select field "' . ($name ?: 'unknown') . '" has no options.',
                            'path' => "config.{$pageIndex}.items.{$itemIndex}",
                        ];
                    }
                }
            }
        }

        if ($inputCount > 0 && !$hasSubmit) {
            $issues[] = [
                'code' => 'missing_submit_button',
                'severity' => 'error',
                'message' => 'Task form has input fields but no submit button.',
            ];
        }

        $hasErrors = count(array_filter($issues, fn (array $i): bool => ($i['severity'] ?? '') === 'error')) > 0;

        return [
            'valid' => !$hasErrors,
            'issues' => $issues,
        ];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>|null
     */
    private function buildField(array $field): ?array
    {
        $variable = (string) ($field['variable'] ?? $field['name'] ?? '');
        if ($variable === '') {
            return null;
        }

        $type = strtolower((string) ($field['type'] ?? 'text'));
        $label = (string) ($field['label'] ?? $variable);
        $selector = $this->slug($variable);
        $placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';
        $validation = ($field['required'] ?? false) === true ? 'required' : '';

        if (in_array($type, ['select', 'dropdown'], true)) {
            return $this->buildSelectField($field, $selector, $label, $validation, $placeholder);
        }

        $mapping = match ($type) {
            'textarea', 'multiline' => ['component' => 'FormTextArea', 'dataFormat' => 'string', 'inputType' => 'text'],
            'checkbox' => ['component' => 'FormCheckbox', 'dataFormat' => 'boolean', 'inputType' => 'checkbox'],
            'date', 'datetime' => ['component' => 'FormDatePicker', 'dataFormat' => 'datetime', 'inputType' => 'date'],
            'number' => ['component' => 'FormInput', 'dataFormat' => 'float', 'inputType' => 'number'],
            'email' => ['component' => 'FormInput', 'dataFormat' => 'string', 'inputType' => 'email'],
            'password' => ['component' => 'FormInput', 'dataFormat' => 'string', 'inputType' => 'password'],
            default => ['component' => 'FormInput', 'dataFormat' => 'string', 'inputType' => 'text'],
        };

        $config = [
            'name' => $selector,
            'label' => $label,
            'helper' => null,
            'dataFormat' => $mapping['dataFormat'],
            'validation' => $validation,
            'placeholder' => $placeholder,
            'type' => $mapping['inputType'],
        ];

        if (isset($field['default']) && $field['default'] !== '') {
            $config['defaultValue'] = $field['default'];
        }

        if ($mapping['component'] === 'FormTextArea') {
            $config['rows'] = (int) ($field['rows'] ?? 2);
            unset($config['type']);
        }

        if ($mapping['component'] === 'FormCheckbox') {
            unset($config['type'], $config['placeholder']);
        }

        return ScreenControlCatalog::cloneControl($mapping['component'], $config, $label);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function buildSelectField(
        array $field,
        string $selector,
        string $label,
        string $validation,
        string $placeholder
    ): array {
        $options = [];
        foreach ($field['options'] ?? [] as $option) {
            if (is_string($option)) {
                $options[] = ['value' => $this->slug($option), 'content' => $option];
                continue;
            }

            if (!is_array($option)) {
                continue;
            }

            $value = $option['value'] ?? $option['id'] ?? null;
            if ($value === null) {
                continue;
            }

            $options[] = [
                'value' => (string) $value,
                'content' => (string) ($option['label'] ?? $option['content'] ?? $value),
            ];
        }

        if ($options === []) {
            $options[] = ['value' => 'option_1', 'content' => 'Option 1'];
        }

        return ScreenControlCatalog::cloneControl('FormSelect', [
            'name' => $selector,
            'label' => $label,
            'helper' => null,
            'dataFormat' => 'string',
            'validation' => $validation,
            'placeholder' => $placeholder,
            'options' => $options,
        ], $label);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSubmitButton(): array
    {
        return ScreenControlCatalog::cloneControl('FormButton', [
            'event' => 'submit',
            'label' => 'Submit',
            'variant' => 'primary',
            'defaultSubmit' => true,
        ], 'Submit Button');
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? 'field';
        $value = trim($value, '_');

        return $value !== '' ? strtolower($value) : 'field';
    }
}
