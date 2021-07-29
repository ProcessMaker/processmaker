<?php

namespace Tests\Feature\Processes;

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

    /**
     * Test nav buttons in nested screen
     *
     * @return void
     */
    public function testNestedNavButtons()
    {
        // Create an admin user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Save the file contents and convert them to an UploadedFile
        $fileName = realpath(__DIR__ . '/../../Fixtures/nested_with_navbar.json');
        $file = new UploadedFile($fileName, 'nested_with_navbar.json', null, null, null, true);

        // Test to ensure our standard user cannot import a screen
        $this->user = $adminUser;
        $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);

        $consolidator = new ScreenConsolidator(Screen::orderBy('id', 'desc')->first());

        $consolidatedScreen = $consolidator->call();

        $pages = $consolidatedScreen['config'];
        // Page 0 = Page 0 from Root + Page0 from Nested1
        // Page 1 = Page 1 from Root + Page0 from Nested2
        // Page 2 = Page 1 from Nested 1
        // Page 3 = Page 2 from Nested 1
        // Page 4 = Page 1 from Nested 2
        // Page 5 = Page 2 from Nested 2

        // Page 0 has a NavButton to Page 2
        $this->assertPageHas($pages[0]['items'], [
            ['editor-control' => 'PageNavigation', 'config' => ['eventData' => 2]],
        ], 'Page 0 does not have a NavButton to Page 2');

        // Page 0 has a RecordList to Page 1
        $this->assertPageHas($pages[0]['items'], [
            ['editor-control' => 'FormRecordList', 'config' => ['form' => 2]],
        ], 'Page 0 does not have a RecordList to Page 1');

        // Page 1 has a NavButton to Page 4
        $this->assertPageHas($pages[1]['items'], [
            ['editor-control' => 'PageNavigation', 'config' => ['eventData' => 4]],
        ], 'Page 1 does not have a NavButton to Page 4');

        // Page 2 has a NavButton to Page 3
        $this->assertPageHas($pages[2]['items'], [
            ['editor-control' => 'PageNavigation', 'config' => ['eventData' => 3]],
        ], 'Page 2 does not have a NavButton to Page 3');

        // Page 3 has a NavButton to Page 1
        $this->assertPageHas($pages[3]['items'], [
            ['editor-control' => 'PageNavigation', 'config' => ['eventData' => 1]],
        ], 'Page 3 does not have a NavButton to Page 1');

        // Page 4 is the Page 1 from Nested 2
        $this->assertEquals('Page 1 from Nested', $pages[4]['name'], 'Page 4 must be Page 1 from Nested 2');

        // Page 5 is the Page 2 from Nested 2
        $this->assertEquals('Page 2 from Nested', $pages[5]['name'], 'Page 5 must be Page 2 from Nested 2');
    }

    /**
     * Test read only recordlist without editable form
     *
     * @return void
     */
    public function testRecordListWithoutRecordForm()
    {
        // Create an admin user
        $adminUser = factory(User::class)->create([
            'username' => 'admin',
            'is_administrator' => true,
        ]);

        // Save the file contents and convert them to an UploadedFile
        $fileName = realpath(__DIR__ . '/../../Fixtures/record_without_record_form.json');
        $file = new UploadedFile($fileName, 'record_without_record_form.json', null, null, null, true);

        // Test to ensure our standard user cannot import a screen
        $this->user = $adminUser;
        $this->apiCall('POST', '/screens/import', [
            'file' => $file,
        ]);

        $consolidator = new ScreenConsolidator(Screen::orderBy('id', 'desc')->first());

        $consolidatedScreen = $consolidator->call();

        $pages = $consolidatedScreen['config'];

        // Page 0 has a NavButton to Page 2
        $this->assertPageHas($pages[0]['items'], [
            ['editor-control' => 'FormRecordList', 'config' => ['form' => null]],
        ], 'Readonly FormRecordList should be empty');
    }

    private function assertPageHas($page, $content, $message)
    {
        $this->assertTrue($this->doesPageContains($page, $content), $message);
    }

    private function doesPageContains($page, $content)
    {
        foreach ($content as $key => $value) {
            if (is_numeric($key)) {
                $found = false;
                foreach ($page as $item) {
                    if ($this->doesPageContains($item, $value)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            } else {
                if (!isset($page[$key])) {
                    return false;
                }
                $item = $page[$key];
            }
            if (is_array($value)) {
                $this->doesPageContains($item, $value);
            } else {
                if ($value != $item) {
                    return false;
                }
            }
        }
        return true;
    }
}
