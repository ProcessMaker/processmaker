<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

use ProcessMaker\Mcp\Process\ScreenControlCatalog;

class DynaformConverter
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $fieldRegistry = [];

    /**
     * @param  array<string, mixed>  $dynaform
     * @return array{
     *     config: array<int, mixed>,
     *     grid_definitions: array<int, array<string, mixed>>,
     *     custom_css: string|null,
     *     field_registry: array<int, array<string, mixed>>,
     *     warnings: array<int, string>
     * }
     */
    public function toScreenConfig(array $dynaform): array
    {
        $this->fieldRegistry = [];
        $content = $this->parseContent($dynaform);
        $title = $dynaform['DYN_TITLE'] ?? $content['name'] ?? 'Migrated Screen';
        $warnings = [];
        $items = [];
        $gridDefinitions = [];
        $cssConverter = new CssScopeConverter();
        $cssChunks = [];

        foreach ($this->extractFields($content) as $field) {
            $type = strtolower((string) ($field['type'] ?? ''));

            if ($type === 'grid') {
                $gridDefinitions[] = $field;
                $this->registerGridField($field, $cssConverter);
                foreach ($this->extractGridColumns($field) as $columnField) {
                    $this->appendInlineFieldCss($cssConverter, $columnField, $cssChunks);
                }

                continue;
            }

            $converted = $this->convertField($field, $warnings);
            if ($converted === null) {
                continue;
            }

            if (isset($converted[0]) && is_array($converted[0])) {
                foreach ($converted as $item) {
                    $items[] = $item;
                }
            } else {
                $items[] = $converted;
            }

            $this->appendInlineFieldCss($cssConverter, $field, $cssChunks);
        }

        if ($items === [] && $gridDefinitions === []) {
            $items[] = $this->placeholderInput('migrated_field', 'Migrated Field');
            $warnings[] = 'No supported controls found; placeholder field was added.';
        }

        if ($this->hasInputFields($items)) {
            $items[] = $this->buildSubmitButton();
        }

        $rawCss = $this->extractCustomCss($content);
        if (is_string($rawCss) && $rawCss !== '') {
            $convertedCss = $cssConverter->convert($rawCss, $this->fieldRegistry);
            $warnings = array_merge($warnings, $convertedCss['warnings']);
            if (is_string($convertedCss['css']) && $convertedCss['css'] !== '') {
                array_unshift($cssChunks, $convertedCss['css']);
            }
        }

        return [
            'config' => [
                [
                    'name' => $title,
                    'computed' => [],
                    'items' => $items,
                ],
            ],
            'grid_definitions' => $gridDefinitions,
            'custom_css' => $cssChunks === [] ? null : implode("\n\n", $cssChunks),
            'field_registry' => $this->fieldRegistry,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $gridField
     * @return array{items: array<int, array<string, mixed>>, options_list: array<int, array<string, string>>, warnings: array<int, string>}
     */
    public function convertGridColumns(array $gridField): array
    {
        $warnings = [];
        $items = [];
        $optionsList = [];

        foreach ($this->extractGridColumns($gridField) as $columnField) {
            $converted = $this->convertField($columnField, $warnings);
            if ($converted === null || (isset($converted[0]) && is_array($converted[0]))) {
                continue;
            }

            $items[] = $converted;
            $optionsList[] = [
                'value' => $converted['config']['name'],
                'content' => (string) ($converted['config']['label'] ?? $converted['config']['name']),
            ];
        }

        if ($items === []) {
            $items[] = $this->placeholderInput('grid_column', 'Grid Column');
            $optionsList[] = ['value' => 'grid_column', 'content' => 'Grid Column'];
            $warnings[] = 'Grid had no supported columns; placeholder column was added.';
        }

        return [
            'items' => $items,
            'options_list' => $optionsList,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $gridField
     * @return array<string, mixed>
     */
    public function buildRecordListItem(array $gridField, string $nestedScreenId, ?array $columns = null): array
    {
        $variable = $gridField['variable'] ?? $gridField['name'] ?? 'grid';
        $label = $gridField['label'] ?? $gridField['name'] ?? 'Record List';
        $slug = $this->slug((string) $variable);
        $columns ??= $this->convertGridColumns($gridField);
        $optionsList = $columns['options_list'];

        return ScreenControlCatalog::cloneControl('FormRecordList', [
            'form' => $nestedScreenId,
            'icon' => 'fas fa-th-list',
            'name' => $slug,
            'label' => (string) $label,
            'editable' => true,
            'customCssSelector' => $slug,
            'fields' => [
                'jsonData' => json_encode($optionsList),
                'editIndex' => null,
                'dataSource' => 'provideData',
                'optionsList' => $optionsList,
                'removeIndex' => null,
                'showJsonEditor' => false,
                'showOptionCard' => false,
                'showRemoveWarning' => false,
            ],
        ], (string) $label);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function hasInputFields(array $items): bool
    {
        foreach ($items as $item) {
            $component = (string) ($item['component'] ?? '');
            if (in_array($component, ['FormInput', 'FormTextArea', 'FormSelect', 'FormSelectList', 'FormCheckbox', 'FormDatePicker', 'FormRecordList'], true)) {
                return true;
            }
        }

        return false;
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

    /**
     * @param  array<string, mixed>  $dynaform
     * @return array<string, mixed>
     */
    private function parseContent(array $dynaform): array
    {
        $raw = $dynaform['DYN_CONTENT'] ?? '{}';
        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<int, array<string, mixed>>
     */
    private function extractFields(array $content): array
    {
        $fields = [];
        $rootItems = $content['items'] ?? [];

        if (!is_array($rootItems)) {
            return $fields;
        }

        foreach ($rootItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) === 'form' && is_array($item['items'] ?? null)) {
                foreach ($item['items'] as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    foreach ($row as $field) {
                        if (is_array($field)) {
                            $fields[] = $field;
                        }
                    }
                }

                continue;
            }

            $fields[] = $item;
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $gridField
     * @return array<int, array<string, mixed>>
     */
    private function extractGridColumns(array $gridField): array
    {
        $columns = [];

        foreach ($gridField['columns'] ?? [] as $column) {
            if (!is_array($column)) {
                continue;
            }

            if (isset($column['type'])) {
                $columns[] = $column;
                continue;
            }

            foreach ($column['fields'] ?? [] as $nestedField) {
                if (is_array($nestedField)) {
                    $columns[] = $nestedField;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function extractCustomCss(array $content): ?string
    {
        foreach (['css', 'customCss', 'custom_css', 'stylesheet'] as $key) {
            $value = $content[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>|array<int, array<string, mixed>>|null
     */
    private function convertField(array $field, array &$warnings): array|null
    {
        $type = strtolower((string) ($field['type'] ?? 'text'));
        $label = $field['label'] ?? $field['name'] ?? 'Field';
        $variable = $field['variable'] ?? $field['var_name'] ?? $this->slug((string) $label);

        if (in_array($type, ['title', 'subtitle', 'button'], true)) {
            return null;
        }

        $selector = $this->slug((string) $variable);

        if ($type === 'hidden') {
            $this->registerConvertedField($field, $selector, 'FormInput');

            return $this->buildInput($variable, $label, 'FormInput', 'string', 'text', null, true, null, $selector);
        }

        if (in_array($type, ['radio', 'radiogroup'], true)) {
            $this->registerConvertedField($field, $selector, 'FormSelect');

            return $this->buildSelect($field, $variable, $label, $warnings, false);
        }

        if (in_array($type, ['checkgroup', 'checkboxlist'], true)) {
            $this->registerConvertedField($field, $selector, 'FormSelect');

            return $this->buildSelect($field, $variable, $label, $warnings, true);
        }

        if ($type === 'file') {
            $warnings[] = "File upload '{$variable}' requires PM4 file control review.";
            $this->registerConvertedField($field, $selector, 'FormInput');

            return $this->buildInput($variable, $label, 'FormInput', 'string', 'text', null, false, null, $selector);
        }

        $mapping = match ($type) {
            'textarea' => ['component' => 'FormTextArea', 'dataFormat' => 'string', 'inputType' => 'text'],
            'dropdown', 'suggest', 'select' => ['component' => 'FormSelect', 'dataFormat' => 'string', 'inputType' => 'text'],
            'checkbox' => ['component' => 'FormCheckbox', 'dataFormat' => 'boolean', 'inputType' => 'checkbox'],
            'date', 'datetime' => ['component' => 'FormDatePicker', 'dataFormat' => 'datetime', 'inputType' => 'text'],
            'currency' => ['component' => 'FormInput', 'dataFormat' => 'currency', 'inputType' => 'text'],
            'percentage' => ['component' => 'FormInput', 'dataFormat' => 'percentage', 'inputType' => 'text'],
            'text', 'email', 'phone', 'number' => ['component' => 'FormInput', 'dataFormat' => 'string', 'inputType' => $type === 'number' ? 'number' : 'text'],
            default => null,
        };

        if ($mapping === null) {
            $warnings[] = "Unsupported dynaform control type '{$type}' for field '{$variable}'.";
            $mapping = ['component' => 'FormInput', 'dataFormat' => 'string', 'inputType' => 'text'];
        }

        $this->registerConvertedField($field, $selector, $mapping['component']);

        return $this->buildInput(
            $variable,
            $label,
            $mapping['component'],
            $mapping['dataFormat'],
            $mapping['inputType'],
            !empty($field['required']) ? 'required' : null,
            false,
            $field['placeholder'] ?? null,
            $selector
        );
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>
     */
    private function buildSelect(
        array $field,
        string $variable,
        mixed $label,
        array &$warnings,
        bool $multiple
    ): array {
        $options = [];
        foreach ($field['options'] ?? [] as $option) {
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
            $warnings[] = "Select field '{$variable}' has no options; placeholder option added.";
            $options[] = ['value' => 'option_1', 'content' => 'Option 1'];
        }

        $config = [
            'name' => $this->slug((string) $variable),
            'label' => is_string($label) ? $label : (string) $variable,
            'helper' => null,
            'dataFormat' => 'string',
            'validation' => !empty($field['required']) ? 'required' : null,
            'placeholder' => null,
            'options' => $options,
            'customCssSelector' => $this->slug((string) $variable),
        ];

        if ($multiple) {
            $config['multiple'] = true;
        }

        return ScreenControlCatalog::cloneControl('FormSelect', $config, (string) $label);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInput(
        string $variable,
        mixed $label,
        string $component,
        string $dataFormat,
        string $inputType,
        ?string $validation = null,
        bool $hidden = false,
        ?string $placeholder = null,
        ?string $customCssSelector = null
    ): array {
        $selector = $customCssSelector ?? $this->slug((string) $variable);
        $labelText = is_string($label) ? $label : (string) $variable;

        $config = [
            'name' => $selector,
            'label' => $labelText,
            'helper' => null,
            'dataFormat' => $dataFormat,
            'validation' => $validation,
            'placeholder' => $placeholder,
            'hidden' => $hidden,
            'customCssSelector' => $selector,
        ];

        if ($component === 'FormInput') {
            $config['type'] = $inputType;
        }

        if ($component === 'FormTextArea') {
            $config['rows'] = 2;
        }

        return ScreenControlCatalog::cloneControl($component, $config, $labelText);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function registerConvertedField(array $field, string $selector, string $component): void
    {
        $this->fieldRegistry[] = (new CssScopeConverter())->registryEntryFromPm3Field(
            $field,
            $selector,
            $component
        );
    }

    /**
     * @param  array<string, mixed>  $gridField
     */
    private function registerGridField(array $gridField, CssScopeConverter $cssConverter): void
    {
        $selector = $this->slug((string) ($gridField['variable'] ?? $gridField['name'] ?? 'grid'));
        $this->fieldRegistry[] = $cssConverter->registryEntryFromPm3Field(
            $gridField,
            $selector,
            'FormRecordList'
        );

        foreach ($this->extractGridColumns($gridField) as $columnField) {
            $columnSelector = $this->slug((string) ($columnField['variable'] ?? $columnField['name'] ?? 'column'));
            $this->fieldRegistry[] = $cssConverter->registryEntryFromPm3Field(
                $columnField,
                $columnSelector,
                'FormInput'
            );
        }
    }

    /**
     * @param  array<int, string>  $cssChunks
     * @param  array<string, mixed>  $field
     */
    private function appendInlineFieldCss(CssScopeConverter $cssConverter, array $field, array &$cssChunks): void
    {
        $selector = $this->slug((string) ($field['variable'] ?? $field['name'] ?? 'field'));
        $inlineCss = $cssConverter->buildInlineFieldCss($field, $selector);

        if (is_string($inlineCss) && $inlineCss !== '') {
            $cssChunks[] = $inlineCss;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function placeholderInput(string $name, string $label): array
    {
        return $this->buildInput($name, $label, 'FormInput', 'string', 'text');
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? 'field';
        $value = trim($value, '_');

        return $value !== '' ? strtolower($value) : 'field';
    }
}
