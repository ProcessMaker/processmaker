<?php

namespace Tests\Unit\Mcp\Migration;

use ProcessMaker\Mcp\Migration\CssScopeConverter;
use Tests\TestCase;

class CssScopeConverterTest extends TestCase
{
    public function testScopesFieldSelectorToPm4CustomCssFormat(): void
    {
        $converter = new CssScopeConverter();
        $registry = [[
            'selector' => 'invoice_number',
            'component' => 'FormInput',
            'pm3_type' => 'text',
            'pm3_id' => 'invoice_number',
            'pm3_name' => null,
            'pm3_variable' => 'invoice_number',
        ]];

        $result = $converter->convert('#invoice_number { color: red; font-size: 14px; }', $registry);

        $this->assertStringContainsString("[selector='invoice_number']", (string) $result['css']);
        $this->assertStringContainsString("[selector='invoice_number'] input", (string) $result['css']);
        $this->assertStringContainsString('color: red', (string) $result['css']);
        $this->assertStringNotContainsString('font-size', (string) $result['css']);
    }

    public function testScopesTagSelectorsByControlType(): void
    {
        $converter = new CssScopeConverter();
        $registry = [
            [
                'selector' => 'status',
                'component' => 'FormSelect',
                'pm3_type' => 'dropdown',
                'pm3_id' => null,
                'pm3_name' => null,
                'pm3_variable' => 'status',
            ],
            [
                'selector' => 'notes',
                'component' => 'FormTextArea',
                'pm3_type' => 'textarea',
                'pm3_id' => null,
                'pm3_name' => null,
                'pm3_variable' => 'notes',
            ],
        ];

        $result = $converter->convert("select { border: 1px solid #ccc; }\ntextarea { min-height: 80px; }", $registry);

        $this->assertStringContainsString("[selector='status'] select", (string) $result['css']);
        $this->assertStringContainsString('border: 1px solid #ccc', (string) $result['css']);
        $this->assertStringContainsString("[selector='notes'] textarea", (string) $result['css']);
        $this->assertStringContainsString('min-height: 80px', (string) $result['css']);
    }

    public function testSkipsPm3SkinNoiseSelectors(): void
    {
        $converter = new CssScopeConverter();

        $result = $converter->convert('.x-form-invalid { color: red; }', []);

        $this->assertNull($result['css']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function testBuildInlineFieldCssUsesScopedSelectors(): void
    {
        $converter = new CssScopeConverter();

        $css = $converter->buildInlineFieldCss([
            'type' => 'text',
            'textTransform' => 'uppercase',
            'colSpan' => 6,
        ], 'customer_name');

        $this->assertStringContainsString("[selector='customer_name']", (string) $css);
        $this->assertStringContainsString('text-transform: uppercase', (string) $css);
        $this->assertStringContainsString('width: 50%', (string) $css);
    }
}
