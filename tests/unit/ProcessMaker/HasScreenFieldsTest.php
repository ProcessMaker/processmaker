<?php

use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class HasScreenFieldsTest extends TestCase
{
    public function testLoadScreenFields()
    {
        $screenCache = ScreenCacheFactory::getScreenCache();
        $screen = Screen::factory()->create([
            'id' => 1,
            'config' => [
                [
                    'items' => [
                        [
                            'component' => 'FormInput',
                            'config' => [
                                'name' => 'field1',
                                'label' => 'Field 1',
                                'dataFormat' => 'string',
                            ],
                        ],
                        [
                            'component' => 'FormInput',
                            'config' => [
                                'name' => 'field2',
                                'label' => 'Field 2',
                                'dataFormat' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $expectedFields = [
            [
                'label' => 'Field 1',
                'field' => 'field1',
                'sortable' => true,
                'default' => false,
                'format' => 'string',
                'mask' => null,
                'isSubmitButton' => false,
                'encryptedConfig' => null,
            ],
            [
                'label' => 'Field 2',
                'field' => 'field2',
                'sortable' => true,
                'default' => false,
                'format' => 'string',
                'mask' => null,
                'isSubmitButton' => false,
                'encryptedConfig' => null,
            ],
        ];
        $key = $screenCache->createKey([
            'process_id' => 0,
            'process_version_id' => 0,
            'language' => 'all',
            'screen_id' => (int) $screen->id,
            'screen_version_id' => 0,
        ]) . '_fields';
        $screenCache->set($key, null);

        $fields = json_decode(json_encode($screen->fields), true);

        $cacheFields = json_decode(json_encode($screenCache->get($key)), true);

        $this->assertEquals($expectedFields, $fields);
        $this->assertEquals($expectedFields, $cacheFields);
    }

    public function testLoadScreenFieldsFromCache()
    {
        $screenCache = ScreenCacheFactory::getScreenCache();
        $screen = Screen::factory()->create([
            'id' => 1,
            'config' => [
                [
                    'items' => [
                        [
                            'component' => 'FormInput',
                            'config' => [
                                'name' => 'field1',
                                'label' => 'Field 1',
                                'dataFormat' => 'string',
                            ],
                        ],
                        [
                            'component' => 'FormInput',
                            'config' => [
                                'name' => 'field2',
                                'label' => 'Field 2',
                                'dataFormat' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $expectedFields = [
            [
                'label' => 'Field 1 (cached)',
                'field' => 'field1',
                'sortable' => true,
                'default' => false,
                'format' => 'string',
                'mask' => null,
                'isSubmitButton' => false,
                'encryptedConfig' => null,
            ],
            [
                'label' => 'Field 2 (cached)',
                'field' => 'field2',
                'sortable' => true,
                'default' => false,
                'format' => 'string',
                'mask' => null,
                'isSubmitButton' => false,
                'encryptedConfig' => null,
            ],
        ];
        $key = $screenCache->createKey([
            'process_id' => 0,
            'process_version_id' => 0,
            'language' => 'all',
            'screen_id' => (int) $screen->id,
            'screen_version_id' => 0,
        ]) . '_fields';
        $screenCache->set($key, $expectedFields);

        $fields = json_decode(json_encode($screen->fields), true);

        $cacheFields = json_decode(json_encode($screenCache->get($key)), true);

        $this->assertEquals($expectedFields, $fields);
        $this->assertEquals($expectedFields, $cacheFields);
    }
}
