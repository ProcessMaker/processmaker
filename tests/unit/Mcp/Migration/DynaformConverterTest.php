<?php

namespace Tests\Unit\Mcp\Migration;

use ProcessMaker\Mcp\Migration\DynaformConverter;
use Tests\TestCase;

class DynaformConverterTest extends TestCase
{
    public function testConvertsPm3DynaformFieldsToScreenConfig(): void
    {
        $converter = new DynaformConverter();

        $result = $converter->toScreenConfig([
            'DYN_TITLE' => 'Invoice Form',
            'DYN_CONTENT' => json_encode([
                'css' => '#invoice_number { color: navy; }',
                'name' => 'Invoice Form',
                'items' => [
                    [
                        'type' => 'form',
                        'items' => [
                            [
                                [
                                    'type' => 'text',
                                    'variable' => 'invoice_number',
                                    'label' => 'Invoice Number',
                                    'required' => true,
                                ],
                                [
                                    'type' => 'textarea',
                                    'variable' => 'notes',
                                    'label' => 'Notes',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertSame('Invoice Form', $result['config'][0]['name']);
        $this->assertCount(3, $result['config'][0]['items']);
        $this->assertSame('FormInput', $result['config'][0]['items'][0]['component']);
        $this->assertSame('FormTextArea', $result['config'][0]['items'][1]['component']);
        $this->assertSame('FormButton', $result['config'][0]['items'][2]['component']);
        $this->assertSame('required', $result['config'][0]['items'][0]['config']['validation']);
        $this->assertSame('invoice_number', $result['config'][0]['items'][0]['config']['customCssSelector']);
        $this->assertStringContainsString("[selector='invoice_number']", (string) $result['custom_css']);
        $this->assertStringContainsString('color: navy', (string) $result['custom_css']);
        $this->assertSame([], $result['warnings']);
    }

    public function testAddsPlaceholderWhenNoSupportedControlsExist(): void
    {
        $converter = new DynaformConverter();

        $result = $converter->toScreenConfig([
            'DYN_TITLE' => 'Title Only',
            'DYN_CONTENT' => json_encode([
                'items' => [
                    ['type' => 'title', 'label' => 'Header'],
                ],
            ]),
        ]);

        $this->assertCount(2, $result['config'][0]['items']);
        $this->assertSame('FormButton', $result['config'][0]['items'][1]['component']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function testConvertsRadioAndHiddenControls(): void
    {
        $converter = new DynaformConverter();

        $result = $converter->toScreenConfig([
            'DYN_TITLE' => 'Mixed Form',
            'DYN_CONTENT' => json_encode([
                'items' => [
                    [
                        'type' => 'form',
                        'items' => [
                            [[
                                'type' => 'radio',
                                'variable' => 'priority',
                                'label' => 'Priority',
                                'options' => [
                                    ['value' => 'high', 'label' => 'High'],
                                ],
                            ]],
                            [[
                                'type' => 'hidden',
                                'variable' => 'token',
                                'label' => 'Token',
                            ]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertSame('FormSelect', $result['config'][0]['items'][0]['component']);
        $this->assertTrue($result['config'][0]['items'][1]['config']['hidden']);
    }

    public function testExtractsGridDefinitionsWithoutFlattening(): void
    {
        $converter = new DynaformConverter();

        $result = $converter->toScreenConfig([
            'DYN_TITLE' => 'Grid Form',
            'DYN_CONTENT' => json_encode([
                'items' => [
                    [
                        'type' => 'form',
                        'items' => [
                            [[
                                'type' => 'grid',
                                'variable' => 'items_grid',
                                'label' => 'Items',
                                'columns' => [
                                    [
                                        'type' => 'text',
                                        'variable' => 'item_name',
                                        'label' => 'Item Name',
                                    ],
                                ],
                            ]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertCount(1, $result['grid_definitions']);
        $this->assertSame('items_grid', $result['grid_definitions'][0]['variable']);

        $columns = $converter->convertGridColumns($result['grid_definitions'][0]);
        $this->assertSame('FormInput', $columns['items'][0]['component']);
        $this->assertSame('item_name', $columns['options_list'][0]['value']);
    }
}
