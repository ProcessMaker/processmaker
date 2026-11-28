<?php

declare(strict_types=1);

namespace ProcessMaker\Mcp\Migration;

class CssScopeConverter
{
    /**
     * @var array<string, array<int, string>>
     */
    private const PM3_TYPE_SCOPES = [
        'text' => ['input', 'textarea', '.form-control'],
        'email' => ['input', '.form-control'],
        'phone' => ['input', '.form-control'],
        'number' => ['input', '.form-control'],
        'textarea' => ['textarea', '.form-control'],
        'dropdown' => ['select', '.form-control', '.multiselect'],
        'select' => ['select', '.form-control', '.multiselect'],
        'suggest' => ['input', '.form-control', '.multiselect'],
        'checkbox' => ['input[type="checkbox"]', '.custom-control-input'],
        'radio' => ['input[type="radio"]', '.custom-control-input', 'select', '.form-control'],
        'checkgroup' => ['select', '.form-control', '.multiselect'],
        'checkboxlist' => ['select', '.form-control', '.multiselect'],
        'date' => ['input', '.form-control', '.vdp-datepicker input'],
        'datetime' => ['input', '.form-control', '.vdp-datepicker input'],
        'currency' => ['input', '.form-control'],
        'percentage' => ['input', '.form-control'],
        'file' => ['input[type="file"]', '.form-control'],
        'hidden' => ['input[type="hidden"]'],
        'grid' => ['.table', '.record-list', '.vuetable'],
        'button' => ['button', '.btn'],
        'title' => ['h1', 'h2', 'h3', '.label'],
        'subtitle' => ['h2', 'h3', 'h4', '.label'],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const PM3_TAG_TYPE_MAP = [
        'input' => ['text', 'email', 'phone', 'number', 'date', 'datetime', 'currency', 'percentage', 'file', 'hidden'],
        'select' => ['dropdown', 'select', 'suggest', 'radio', 'checkgroup', 'checkboxlist'],
        'textarea' => ['textarea'],
        'button' => ['button'],
    ];

    /**
     * @param  array<int, array<string, mixed>>  $fieldRegistry
     * @return array{css: string|null, warnings: array<int, string>}
     */
    public function convert(string $rawCss, array $fieldRegistry): array
    {
        $rawCss = trim($rawCss);
        if ($rawCss === '') {
            return ['css' => null, 'warnings' => []];
        }

        $warnings = [];
        $scopedChunks = [];

        foreach ($this->parseRules($rawCss) as $rule) {
            $converted = $this->convertRule($rule['selectors'], $rule['declarations'], $fieldRegistry, $warnings);
            if ($converted !== null) {
                $scopedChunks[] = $converted;
            }
        }

        if ($scopedChunks === []) {
            return ['css' => null, 'warnings' => $warnings];
        }

        return [
            'css' => implode("\n\n", array_unique($scopedChunks)),
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $pm3Field
     * @return array<string, mixed>
     */
    public function registryEntryFromPm3Field(array $pm3Field, string $selector, string $component): array
    {
        $type = strtolower((string) ($pm3Field['type'] ?? 'text'));

        return [
            'selector' => $selector,
            'component' => $component,
            'pm3_type' => $type,
            'pm3_id' => $pm3Field['id'] ?? null,
            'pm3_name' => $pm3Field['name'] ?? null,
            'pm3_variable' => $pm3Field['variable'] ?? $pm3Field['var_name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $pm3Field
     */
    public function buildInlineFieldCss(array $pm3Field, string $selector): ?string
    {
        $declarations = [];

        $textTransform = $pm3Field['textTransform'] ?? null;
        if (is_string($textTransform) && $textTransform !== '' && $textTransform !== 'none') {
            $declarations[] = 'text-transform: ' . $textTransform . ';';
        }

        $colSpan = (int) ($pm3Field['colSpan'] ?? 0);
        if ($colSpan > 0 && $colSpan < 12) {
            $width = round(($colSpan / 12) * 100, 2);
            $declarations[] = 'width: ' . $width . '%;';
        }

        if ($declarations === []) {
            return null;
        }

        return $this->scopeDeclarations($selector, $pm3Field, implode("\n  ", $declarations));
    }

    /**
     * @return array<int, array{selectors: string, declarations: string}>
     */
    private function parseRules(string $css): array
    {
        $css = preg_replace('/\/\*.*?\*\//s', '', $css) ?? $css;
        $rules = [];

        if (!preg_match_all('/([^{]+)\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            $selectors = trim($match[1]);
            $declarations = trim($match[2]);

            if ($selectors === '' || $declarations === '') {
                continue;
            }

            $rules[] = [
                'selectors' => $selectors,
                'declarations' => $declarations,
            ];
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fieldRegistry
     * @param  array<int, string>  $warnings
     */
    private function convertRule(
        string $selectors,
        string $declarations,
        array $fieldRegistry,
        array &$warnings
    ): ?string {
        $declarations = $this->sanitizeDeclarations($declarations);
        if ($declarations === '') {
            return null;
        }

        if ($this->isPm3SkinNoise($selectors)) {
            $warnings[] = 'Skipped PM3 skin selector: ' . $selectors;

            return null;
        }

        $selectorParts = array_map('trim', explode(',', $selectors));
        $scopedSelectors = [];

        foreach ($selectorParts as $selector) {
            if ($selector === '') {
                continue;
            }

            $matchedFields = $this->matchFields($selector, $fieldRegistry);

            if ($matchedFields !== []) {
                foreach ($matchedFields as $field) {
                    $scopedSelectors[] = $this->buildScopedSelectorList($field);
                }
                continue;
            }

            $tagMatches = $this->matchFieldsByTag($selector, $fieldRegistry);
            if ($tagMatches !== []) {
                foreach ($tagMatches as $field) {
                    $scopedSelectors[] = $this->buildScopedSelectorList($field);
                }
                continue;
            }

            if ($this->isGlobalSelector($selector)) {
                $warnings[] = 'Global selector kept at screen scope: ' . $selector;
                $scopedSelectors[] = $this->adaptGlobalSelector($selector);

                continue;
            }

            $warnings[] = 'Unmapped PM3 selector skipped: ' . $selector;
        }

        if ($scopedSelectors === []) {
            return null;
        }

        return implode(",\n", array_unique($scopedSelectors)) . " {\n  {$declarations}\n}";
    }

    /**
     * @param  array<int, array<string, mixed>>  $fieldRegistry
     * @return array<int, array<string, mixed>>
     */
    private function matchFields(string $selector, array $fieldRegistry): array
    {
        $needle = $this->normalizeSelectorToken($selector);
        if ($needle === '') {
            return [];
        }

        $matches = [];
        foreach ($fieldRegistry as $field) {
            foreach ([
                $field['selector'] ?? null,
                $field['pm3_id'] ?? null,
                $field['pm3_name'] ?? null,
                $field['pm3_variable'] ?? null,
            ] as $candidate) {
                if (!is_string($candidate) || $candidate === '') {
                    continue;
                }

                if ($this->normalizeSelectorToken($candidate) === $needle) {
                    $matches[] = $field;
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fieldRegistry
     * @return array<int, array<string, mixed>>
     */
    private function matchFieldsByTag(string $selector, array $fieldRegistry): array
    {
        $tag = strtolower(trim($selector));
        $types = self::PM3_TAG_TYPE_MAP[$tag] ?? null;

        if ($types === null) {
            if (in_array($tag, ['.x-form-text', '.x-form-field', '.x-form-item'], true)) {
                $types = self::PM3_TAG_TYPE_MAP['input'];
            } elseif ($tag === '.x-form-checkbox') {
                $types = ['checkbox'];
            }
        }

        if ($types === null) {
            return [];
        }

        return array_values(array_filter(
            $fieldRegistry,
            fn ($field) => in_array($field['pm3_type'] ?? '', $types, true)
        ));
    }

    private function isGlobalSelector(string $selector): bool
    {
        return in_array(strtolower(trim($selector)), ['*', 'body', 'form', 'html'], true);
    }

    private function adaptGlobalSelector(string $selector): string
    {
        return '.screen-container ' . trim($selector);
    }

    private function isPm3SkinNoise(string $selectors): bool
    {
        return (bool) preg_match(
            '/\b(app_menuRight|x-form-invalid|x-panel|x-toolbar|ext-|pmdynaform|processmaker)\b/i',
            $selectors
        );
    }

    private function normalizeSelectorToken(string $selector): string
    {
        $selector = trim($selector);
        $selector = preg_replace('/^#|\.$|\[name=["\']|["\']\]$/', '', $selector) ?? $selector;
        $selector = preg_replace('/^#|^\./', '', $selector) ?? $selector;

        return strtolower($this->slug($selector));
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function buildScopedSelectorList(array $field): string
    {
        $selector = (string) ($field['selector'] ?? 'field');
        $targets = $this->componentTargets($field);
        $parts = ["[selector='{$selector}']"];

        foreach ($targets as $target) {
            $parts[] = "[selector='{$selector}'] {$target}";
        }

        return implode(",\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private function componentTargets(array $field): array
    {
        $type = strtolower((string) ($field['pm3_type'] ?? 'text'));

        return self::PM3_TYPE_SCOPES[$type]
            ?? self::PM3_TYPE_SCOPES['text'];
    }

    private function scopeDeclarations(string $selector, array $field, string $declarations): string
    {
        return $this->buildScopedSelectorList(array_merge($field, ['selector' => $selector]))
            . " {\n  {$declarations}\n}";
    }

    private function sanitizeDeclarations(string $declarations): string
    {
        $lines = preg_split('/;\s*/', $declarations) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(font-family|font-size|filter|opacity|behavior)\s*:/i', $line)) {
                continue;
            }

            $clean[] = $line;
        }

        if ($clean === []) {
            return '';
        }

        return implode(";\n  ", $clean) . ';';
    }

    private function slug(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? 'field';
        $value = trim($value, '_');

        return $value !== '' ? strtolower($value) : 'field';
    }
}
