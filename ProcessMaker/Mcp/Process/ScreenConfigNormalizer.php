<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Process;

/**
 * Ensures screen config matches PM4 Screen Builder expectations before persistence.
 */
final class ScreenConfigNormalizer
{
    /** @var array<string, string> */
    private const COMPONENT_ALIASES = [
        'FormSelectList' => 'FormSelect',
    ];

    /**
     * @param  array<int, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $config): array
    {
        $pages = [];

        foreach ($config as $page) {
            if (!is_array($page)) {
                continue;
            }

            $items = [];
            foreach ($page['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $items[] = $this->normalizeItem($item);
            }

            if ($this->hasInputFields($items) && !$this->hasSubmitButton($items)) {
                $items[] = $this->buildSubmitButton();
            }

            $pages[] = [
                'name' => (string) ($page['name'] ?? 'Form'),
                'computed' => is_array($page['computed'] ?? null) ? $page['computed'] : [],
                'items' => $items,
            ];
        }

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function normalizeItem(array $item): array
    {
        if ($this->isEditorReady($item)) {
            return $item;
        }

        $component = $this->resolveComponent($item);
        $config = is_array($item['config'] ?? null) ? $item['config'] : [];
        $label = (string) ($item['label'] ?? $config['label'] ?? $config['name'] ?? 'Field');

        if (!isset($config['name']) && isset($item['name']) && is_string($item['name'])) {
            $config['name'] = $item['name'];
        }

        if ($component === 'FormSelect' && empty($config['options'])) {
            $config['options'] = [['value' => 'option_1', 'content' => 'Option 1']];
        }

        return ScreenControlCatalog::cloneControl($component, $config, $label);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isEditorReady(array $item): bool
    {
        $inspector = $item['inspector'] ?? null;

        return is_string($item['uuid'] ?? null)
            && ($item['uuid'] ?? '') !== ''
            && is_array($inspector)
            && $inspector !== []
            && is_string($item['editor-component'] ?? null)
            && ($item['editor-component'] ?? '') !== '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveComponent(array $item): string
    {
        $component = (string) ($item['component'] ?? 'FormInput');

        return self::COMPONENT_ALIASES[$component] ?? $component;
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
     * @param  array<int, array<string, mixed>>  $items
     */
    private function hasSubmitButton(array $items): bool
    {
        foreach ($items as $item) {
            $config = is_array($item['config'] ?? null) ? $item['config'] : [];
            if (($item['component'] ?? null) === 'FormButton' && ($config['event'] ?? null) === 'submit') {
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
}
