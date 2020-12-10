<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use ProcessMaker\ScreenConsolidator;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenConsolidatorTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    protected function setUpExecutor()
    {
        ScriptExecutor::setTestConfig('php');
    }

    /**
     * Test to ensure we can export and import
     *
     * @return void
     */
    public function testExportImportProcess()
    {
        // Create an admin user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Save the file contents and convert them to an UploadedFile
        $fileName = realpath(__DIR__ . '/../../Fixtures/test_nested_record_list.json');
        $file = new UploadedFile($fileName, 'test_nested_record_list.json', null, null, null, true);

        // Test to ensure our standard user cannot import a screen
        $this->user = $adminUser;
        $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);

        $consolidator = new ScreenConsolidator(Screen::find(2));
        $expectedResponse = [
            'config' => [
                [
                    'name' => 'Test Nested Record List',
                    'items' => [
                        [
                            'items' => [
                                [
                                    [
                                        'items' => [
                                            // Record List imported from Nested Screen (1)
                                            [
                                                'label' => 'Record List',
                                                'config' => [
                                                    'form' => 1,
                                                    'icon' => 'fas fa-th-list',
                                                    'name' => 'form_record_list_1',
                                                    'label' => 'Relevant Activities',
                                                    'fields' => [
                                                        'jsonData' => '[{"content":"Edad","value":"age"},{"content":"Nombre","value":"name"}]',
                                                        'editIndex' => null,
                                                        'dataSource' => 'provideData',
                                                        'optionsList' => [
                                                            [
                                                                'value' => 'age',
                                                                'content' => 'Edad',
                                                            ],
                                                            [
                                                                'value' => 'name',
                                                                'content' => 'Nombre',
                                                            ],
                                                        ],
                                  'removeIndex' => null,
                                  'showJsonEditor' => false,
                                  'showOptionCard' => false,
                                  'showRemoveWarning' => false,
                                ],
                                'editable' => true,
                              ],
                              'component' => 'FormRecordList',
                              'editor-control' => 'FormRecordList',
                              'editor-component' => 'FormText',
                            ],
                          ],
                          'label' => 'Loop',
                          'config' => [
                              'icon' => 'fas fa-redo',
                              'name' => 'loop_1',
                              'label' => null,
                              'settings' => [
                                  'add' => false,
                                  'type' => 'new',
                                  'times' => '3',
                                  'varname' => 'loop_1',
                              ],
                          ],
                          'component' => 'FormLoop',
                          'container' => true,
                          'editor-control' => 'Loop',
                          'editor-component' => 'Loop',
                        ],
                      ],
                      [
                          // Record List imported from Nested Screen (2)
                          [
                              'label' => 'Record List',
                              'config' => [
                                  'form' => 2,
                                  'icon' => 'fas fa-th-list',
                                  'name' => 'form_record_list_1',
                                  'label' => 'Relevant Activities',
                                  'fields' => [
                                      'jsonData' => '[{"content":"Edad","value":"age"},{"content":"Nombre","value":"name"}]',
                                      'editIndex' => null,
                                      'dataSource' => 'provideData',
                                      'optionsList' => [
                                          [
                                              'value' => 'age',
                                              'content' => 'Edad',
                                          ],
                                          [
                                              'value' => 'name',
                                              'content' => 'Nombre',
                                          ],
                                      ],
                              'removeIndex' => null,
                              'showJsonEditor' => false,
                              'showOptionCard' => false,
                              'showRemoveWarning' => false,
                            ],
                            'editable' => true,
                          ],
                          'component' => 'FormRecordList',
                          'editor-control' => 'FormRecordList',
                          'editor-component' => 'FormText',
                        ],
                      ],
                    ],
                    'label' => 'Multicolumn / Table',
                    'config' => [
                        'icon' => 'fas fa-table',
                        'label' => null,
                        'options' => [
                            [
                                'value' => '1',
                                'content' => '6',
                            ],
                            [
                                'value' => '2',
                                'content' => '6',
                            ],
                        ],
                    ],
                    'component' => 'FormMultiColumn',
                    'container' => true,
                    'editor-control' => 'MultiColumn',
                    'editor-component' => 'MultiColumn',
                  ],
                ],
              ],
              // Page imported from nested screen (1)
              [
                  'name' => 'data',
                  'items' => [
                      [
                          'label' => 'Line Input',
                          'config' => [
                              'icon' => 'far fa-square',
                              'name' => 'name',
                              'type' => 'text',
                              'label' => 'name',
                              'helper' => null,
                              'readonly' => false,
                              'dataFormat' => 'string',
                              'validation' => [],
                              'placeholder' => null,
                          ],
                          'component' => 'FormInput',
                          'editor-control' => 'FormInput',
                          'editor-component' => 'FormInput',
                  ],
                  [
                      'label' => 'Line Input',
                      'config' => [
                          'icon' => 'far fa-square',
                          'name' => 'age',
                          'type' => 'text',
                          'label' => 'age',
                          'helper' => null,
                          'readonly' => false,
                          'dataFormat' => 'int',
                          'validation' => [],
                          'placeholder' => null,
                      ],
                      'component' => 'FormInput',
                      'editor-control' => 'FormInput',
                      'editor-component' => 'FormInput',
                  ],
                ],
              ],
              // Page imported from nested screen (2)
              [
                  'name' => 'data',
                  'items' => [
                      [
                          'label' => 'Line Input',
                          'config' => [
                              'icon' => 'far fa-square',
                              'name' => 'name',
                              'type' => 'text',
                              'label' => 'name',
                              'helper' => null,
                              'readonly' => false,
                              'dataFormat' => 'string',
                              'validation' => [],
                              'placeholder' => null,
                          ],
                          'component' => 'FormInput',
                          'editor-control' => 'FormInput',
                          'editor-component' => 'FormInput',
                  ],
                  [
                      'label' => 'Line Input',
                      'config' => [
                          'icon' => 'far fa-square',
                          'name' => 'age',
                          'type' => 'text',
                          'label' => 'age',
                          'helper' => null,
                          'readonly' => false,
                          'dataFormat' => 'int',
                          'validation' => [],
                          'placeholder' => null,
                      ],
                      'component' => 'FormInput',
                      'editor-control' => 'FormInput',
                      'editor-component' => 'FormInput',
                  ],
                ],
              ],
            ],
            'watchers' => [],
            'custom_css' => '',
            'computed' => [],
        ];

        $consolidatedScreen = $consolidator->call();

        $this->assertEquals($expectedResponse, $consolidatedScreen);
    }
}
