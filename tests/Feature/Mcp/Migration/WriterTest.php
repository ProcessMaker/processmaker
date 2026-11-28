<?php

namespace Tests\Feature\Mcp\Migration;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Mcp\Migration\MigrationLog;
use ProcessMaker\Mcp\Migration\Writer;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\WorkflowServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class WriterTest extends TestCase
{
    use RequestHelper;

    private ?Writer $writerInstance = null;

    private function writer(): Writer
    {
        return $this->writerInstance ??= app(Writer::class);
    }

    protected function tearDown(): void
    {
        if (Storage::disk('local')->exists('migration-logs')) {
            foreach (Storage::disk('local')->files('migration-logs') as $file) {
                Storage::disk('local')->delete($file);
            }
        }

        parent::tearDown();
    }

    public function testCreateProcess(): void
    {
        $this->actingAs($this->user);

        $process = $this->writer()->createProcess([
            'name' => 'MCP Migration Process',
            'description' => 'Created via migration writer',
        ]);

        $this->assertSame('MCP Migration Process', $process->name);
        $this->assertSame($this->user->id, $process->user_id);
        $this->assertNotEmpty($process->bpmn);
    }

    public function testCreateScreen(): void
    {
        $screen = $this->writer()->createScreen([
            'title' => 'MCP Migration Screen',
            'config_json' => [[
                'name' => 'Form',
                'computed' => [],
                'items' => [],
            ]],
        ]);

        $this->assertSame('MCP Migration Screen', $screen->title);
        $this->assertCount(1, $screen->config);
        $this->assertSame('Form', $screen->config[0]['name']);
        $this->assertSame([], $screen->config[0]['items']);
        $this->assertSame([], $screen->config[0]['computed']);
    }

    public function testCreateScript(): void
    {
        $this->actingAs($this->user);

        $script = $this->writer()->createScript([
            'title' => 'MCP Migration Script',
            'code' => '<?php return [];',
        ]);

        $this->assertSame('MCP Migration Script', $script->title);
        $this->assertSame($this->user->id, $script->run_as_user_id);
    }

    public function testValidateProcess(): void
    {
        $process = Process::factory()->create(['user_id' => $this->user->id]);

        $result = $this->writer()->validateProcess($process->id);

        $this->assertSame($process->id, $result['process_id']);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testApplyMigrationBundleCreatesProcessAndScripts(): void
    {
        $this->actingAs($this->user);

        $result = $this->writer()->applyMigrationBundle([
            'source' => [
                'pro_uid' => 'abc-123',
                'title' => 'Invoice Approval',
            ],
            'triggers' => [
                [
                    'TRI_UID' => 'tri-1',
                    'TRI_TITLE' => 'Set Approved',
                    'TRI_WEBBOT' => '<?php @@approved = "YES";',
                ],
            ],
        ]);

        $this->assertNotEmpty($result['process_id']);
        $this->assertCount(1, $result['scripts']);
        $this->assertSame('Set Approved', $result['scripts'][0]['title']);
        $this->assertArrayHasKey('gap_report', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('migration_log', $result);
        $this->assertSame('completed', $result['migration_log']['status']);
    }

    public function testApplyMigrationBundleStoresVariablesAndMetadata(): void
    {
        $this->actingAs($this->user);

        $result = $this->writer()->applyMigrationBundle([
            'source' => ['pro_uid' => 'abc-123', 'title' => 'Variables Process'],
            'variables' => [[
                'var_name' => 'invoice_total',
                'var_default' => '0',
            ]],
            'document_steps' => [[
                'tas_uid' => 'task-1',
                'step_type' => 'INPUT_DOCUMENT',
                'doc_uid' => 'doc-1',
            ]],
        ]);

        $process = Process::findOrFail($result['process_id']);
        $this->assertSame('invoice_total', $process->properties['variables'][0]['name']);
        $this->assertSame('0', $process->properties['request_data_defaults']['invoice_total']);
        $this->assertContains('document_steps', $result['applied_documents']);
    }

    public function testApplyMigrationBundleCreatesScreensFromDynaforms(): void
    {
        $this->actingAs($this->user);

        $result = $this->writer()->applyMigrationBundle([
            'source' => [
                'pro_uid' => 'abc-123',
                'title' => 'Invoice Approval',
            ],
            'dynaforms' => [
                [
                    'DYN_UID' => 'dyn-1',
                    'DYN_TITLE' => 'Invoice Form',
                    'DYN_CONTENT' => json_encode([
                        'items' => [
                            [
                                'type' => 'form',
                                'items' => [
                                    [[
                                        'type' => 'text',
                                        'variable' => 'invoice_number',
                                        'label' => 'Invoice Number',
                                    ]],
                                ],
                            ],
                        ],
                    ]),
                ],
            ],
        ]);

        $this->assertCount(1, $result['screens']);
        $this->assertSame('dyn-1', $result['screens'][0]['pm3_uid']);
        $this->assertSame('Invoice Form', $result['screens'][0]['title']);
    }

    public function testCreateScreenFromDynaform(): void
    {
        $result = $this->writer()->createScreenFromDynaform([
            'DYN_TITLE' => 'Customer Form',
            'DYN_CONTENT' => json_encode([
                'items' => [
                    [
                        'type' => 'form',
                        'items' => [
                            [[
                                'type' => 'text',
                                'variable' => 'customer_name',
                                'label' => 'Customer Name',
                            ]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertSame('Customer Form', $result['screen']->title);
        $this->assertSame('FormInput', $result['screen']->config[0]['items'][0]['component']);
    }

    public function testLinkScriptToTask(): void
    {
        $this->actingAs($this->user);

        $bpmn = file_get_contents(base_path('tests/Feature/Api/processes/inline-task-execution/InlineSequentialScriptServiceTasks.bpmn'));
        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'bpmn' => $bpmn,
        ]);
        $script = Script::factory()->create(['run_as_user_id' => $this->user->id]);

        $updated = $this->writer()->linkScriptToTask($process->id, 'script_a', $script->id);
        $definitions = $updated->getDefinitions(true);
        $xpath = new \DOMXPath($definitions);
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $node = $xpath->query("//*[@id='script_a']")->item(0);

        $this->assertNotNull($node);
        $this->assertSame(
            (string) $script->id,
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'scriptRef')
        );
    }

    public function testAutoLinkMigrationAssetsLinksScreenByTaskId(): void
    {
        $this->actingAs($this->user);

        $bpmn = str_replace(
            'id="node_2"',
            'id="task-1"',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );
        $bpmn = preg_replace('/pm:screenRef="[^"]*"/', '', $bpmn);

        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'bpmn' => $bpmn,
        ]);
        $screen = $this->writer()->createScreen([
            'title' => 'Linked Screen',
            'config_json' => ['items' => []],
        ]);

        $result = $this->writer()->autoLinkMigrationAssets($process->id, [
            'screens' => [[
                'pm3_uid' => 'dyn-1',
                'pm4_id' => $screen->id,
                'title' => 'Linked Screen',
            ]],
            'steps' => [[
                'step_type' => 'DYNAFORM',
                'tas_uid' => 'task-1',
                'dyn_uid' => 'dyn-1',
            ]],
        ]);

        $this->assertCount(1, $result['linked_screens']);
        $this->assertSame('task-1', $result['linked_screens'][0]['element_id']);

        $updated = Process::findOrFail($process->id);
        $xpath = new \DOMXPath($updated->getDefinitions(true));
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $node = $xpath->query("//*[@id='task-1']")->item(0);

        $this->assertSame(
            (string) $screen->id,
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'screenRef')
        );
    }

    public function testAutoLinkMigrationAssetsLinksScreenByTaskTitle(): void
    {
        $this->actingAs($this->user);

        $bpmn = preg_replace(
            '/pm:screenRef="[^"]*"/',
            '',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );

        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'bpmn' => $bpmn,
        ]);
        $screen = $this->writer()->createScreen([
            'title' => 'Linked Screen',
            'config_json' => ['items' => []],
        ]);

        $result = $this->writer()->autoLinkMigrationAssets($process->id, [
            'screens' => [[
                'pm3_uid' => 'dyn-1',
                'pm4_id' => $screen->id,
                'title' => 'Linked Screen',
            ]],
            'tasks' => [[
                'tas_uid' => 'pm3-task-uid',
                'tas_title' => 'New Task',
            ]],
            'steps' => [[
                'step_type' => 'DYNAFORM',
                'tas_uid' => 'pm3-task-uid',
                'dyn_uid' => 'dyn-1',
            ]],
        ]);

        $this->assertCount(1, $result['linked_screens']);
        $this->assertSame('node_2', $result['linked_screens'][0]['element_id']);
    }

    public function testCreateProcessUsesFirstUserWhenNotAuthenticated(): void
    {
        auth()->logout();

        $process = $this->writer()->createProcess([
            'name' => 'Unauthenticated Process',
        ]);

        $this->assertSame(User::orderBy('id')->value('id'), $process->user_id);
    }

    public function testApplyTaskAssignmentsSetsPm4AssignmentAttributes(): void
    {
        $this->actingAs($this->user);

        $bpmn = preg_replace(
            '/pm:screenRef="[^"]*"/',
            '',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );

        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'bpmn' => $bpmn,
        ]);

        $result = $this->writer()->applyTaskAssignments($process->id, [
            'task_assignments' => [[
                'tas_uid' => 'pm3-task-uid',
                'pm4_assignment' => 'user_group',
                'user_uids' => ['pm3-user-1'],
                'group_uids' => ['pm3-group-1'],
            ]],
            'tasks' => [[
                'tas_uid' => 'pm3-task-uid',
                'tas_title' => 'New Task',
            ]],
            'identity_map' => [
                'users' => ['pm3-user-1' => $this->user->id],
                'groups' => ['pm3-group-1' => 1],
            ],
        ]);

        $this->assertCount(1, $result['applied']);
        $this->assertSame('node_2', $result['applied'][0]['element_id']);

        $updated = Process::findOrFail($process->id);
        $xpath = new \DOMXPath($updated->getDefinitions(true));
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $node = $xpath->query("//*[@id='node_2']")->item(0);

        $this->assertSame(
            'user_group',
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment')
        );
        $this->assertSame(
            (string) $this->user->id,
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers')
        );
    }

    public function testCreateCollectionFromReportTable(): void
    {
        if (!class_exists(\ProcessMaker\Plugins\Collections\Models\Collection::class)) {
            $this->markTestSkipped('package-collections is not installed.');
        }

        $this->actingAs($this->user);

        ProcessCategory::factory()->create(['is_system' => true]);

        $result = $this->writer()->createCollectionFromReportTable([
            'rep_tab_uid' => 'rep-1',
            'rep_tab_title' => 'Cases Report',
            'fields' => [[
                'rep_var_field' => 'APP_NUMBER',
                'rep_var_title' => 'Case Number',
                'rep_var_type' => 'string',
            ]],
        ]);

        $this->assertSame('Cases Report', $result['collection']->name);
        $this->assertNotEmpty($result['collection']->columns);
    }

    public function testApplyMigrationBundleAppliesAssignmentsWhenBpmnProvided(): void
    {
        $this->actingAs($this->user);

        $bpmn = preg_replace(
            '/pm:screenRef="[^"]*"/',
            '',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );

        $result = $this->writer()->applyMigrationBundle([
            'source' => [
                'pro_uid' => 'abc-123',
                'title' => 'Assigned Process',
            ],
            'bpmn' => ['xml' => $bpmn],
            'tasks' => [[
                'tas_uid' => 'pm3-task-uid',
                'tas_title' => 'New Task',
            ]],
            'task_assignments' => [[
                'tas_uid' => 'pm3-task-uid',
                'pm4_assignment' => 'previous_task_assignee',
                'user_uids' => [],
                'group_uids' => [],
            ]],
        ]);

        $this->assertCount(1, $result['applied_assignments']);
        $this->assertSame('previous_task_assignee', $result['applied_assignments'][0]['pm4_assignment']);
    }

    public function testApplyMigrationBundleAppliesManualIdentityMapForTaskAssignments(): void
    {
        $this->actingAs($this->user);

        $assignee = User::factory()->create([
            'username' => 'task.assignee',
            'email' => 'task.assignee@example.com',
        ]);

        $bpmn = preg_replace(
            '/pm:screenRef="[^"]*"/',
            '',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );

        $result = $this->writer()->applyMigrationBundle([
            'source' => [
                'pro_uid' => 'abc-123',
                'title' => 'Identity Process',
            ],
            'bpmn' => ['xml' => $bpmn],
            'tasks' => [[
                'tas_uid' => 'pm3-task-uid',
                'tas_title' => 'New Task',
            ]],
            'task_assignments' => [[
                'tas_uid' => 'pm3-task-uid',
                'pm4_assignment' => 'user_group',
                'user_uids' => ['pm3-user-1'],
                'group_uids' => [],
            ]],
            'identity_map' => [
                'users' => ['pm3-user-1' => (string) $assignee->id],
                'groups' => [],
            ],
        ]);

        $this->assertCount(1, $result['applied_assignments']);
        $this->assertSame('node_2', $result['applied_assignments'][0]['element_id']);
    }

    public function testCreateScreenFromDynaformCreatesRecordListForGrid(): void
    {
        $result = $this->writer()->createScreenFromDynaform([
            'DYN_TITLE' => 'Grid Form',
            'DYN_CONTENT' => json_encode([
                'css' => '#items_grid { color: red; }',
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
        ], [[
            'content' => 'input { margin: 0; }',
        ]]);

        $this->assertSame('FormRecordList', $result['screen']->config[0]['items'][0]['component']);
        $this->assertCount(1, $result['nested_screens']);
        $this->assertStringContainsString("[selector='items_grid']", (string) $result['screen']->custom_css);
        $this->assertStringContainsString('color: red', (string) $result['screen']->custom_css);
        $this->assertStringContainsString("[selector='item_name'] input", (string) $result['screen']->custom_css);
        $this->assertStringContainsString('margin: 0', (string) $result['screen']->custom_css);
    }

    public function testApplyAbeConfigurationSetsPm4AbeAttributes(): void
    {
        $this->actingAs($this->user);

        $bpmn = preg_replace(
            '/pm:screenRef="[^"]*"/',
            '',
            file_get_contents(base_path('tests/Fixtures/single_task_with_screen.bpmn'))
        );

        $process = Process::factory()->create([
            'user_id' => $this->user->id,
            'bpmn' => $bpmn,
        ]);
        $emailScreen = $this->writer()->createScreen([
            'title' => 'ABE Email Screen',
            'config_json' => ['items' => []],
        ]);

        $result = $this->writer()->applyAbeConfiguration($process->id, [
            'abe_configurations' => [[
                'tas_uid' => 'pm3-task-uid',
                'dyn_uid' => 'dyn-abe-1',
                'pm4_config_email' => [
                    'subject' => 'RE: Approval',
                    'requireLogin' => true,
                ],
            ]],
            'screens' => [[
                'pm3_uid' => 'dyn-abe-1',
                'pm4_id' => $emailScreen->id,
            ]],
            'tasks' => [[
                'tas_uid' => 'pm3-task-uid',
                'tas_title' => 'New Task',
            ]],
        ]);

        $this->assertCount(1, $result['applied']);
        $this->assertSame((string) $emailScreen->id, $result['applied'][0]['screen_email_ref']);

        $updated = Process::findOrFail($process->id);
        $xpath = new \DOMXPath($updated->getDefinitions(true));
        $xpath->registerNamespace('pm', WorkflowServiceProvider::PROCESS_MAKER_NS);
        $node = $xpath->query("//*[@id='node_2']")->item(0);

        $this->assertSame(
            'true',
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'isActionsByEmail')
        );

        $configEmail = json_decode(
            $node->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'configEmail'),
            true
        );
        $this->assertSame('RE: Approval', $configEmail['subject']);
        $this->assertSame($emailScreen->id, $configEmail['screenEmailRef']);
    }

    public function testGetMigrationLogReturnsVerificationReport(): void
    {
        $this->actingAs($this->user);

        $proUid = 'log-report-pro';
        $this->writer()->applyMigrationBundle([
            'source' => ['pro_uid' => $proUid, 'title' => 'Logged Process'],
            'triggers' => [[
                'TRI_UID' => 'tri-log',
                'TRI_TITLE' => 'Logged Trigger',
                'TRI_WEBBOT' => '<?php return [];',
            ]],
        ]);

        $report = $this->writer()->getMigrationLog($proUid);

        $this->assertSame('completed', $report['summary']['status']);
        $this->assertCount(1, $report['artifacts']['scripts']);
    }

    public function testResumeMigrationBundleSkipsCompletedSteps(): void
    {
        $this->actingAs($this->user);

        $proUid = 'resume-pro-uid';
        $log = MigrationLog::forProUid($proUid);
        $log->start(['source' => ['pro_uid' => $proUid, 'title' => 'Resume Process']]);

        $process = $this->writer()->createProcess(['name' => 'Resume Process']);
        $log->setProcessId((int) $process->id);
        $log->completeStep(MigrationLog::STEP_CREATE_PROCESS);

        $script = $this->writer()->createScript([
            'title' => 'Resume Script',
            'code' => '<?php return [];',
        ]);
        $log->addArtifact('scripts', [
            'pm3_uid' => 'tri-resume',
            'pm4_id' => $script->id,
            'title' => 'Resume Script',
        ]);
        $log->completeStep(MigrationLog::STEP_CREATE_SCRIPTS, ['count' => 1]);

        $processCountBefore = Process::count();

        $result = $this->writer()->resumeMigrationBundle([
            'source' => ['pro_uid' => $proUid, 'title' => 'Resume Process'],
            'triggers' => [[
                'TRI_UID' => 'tri-resume',
                'TRI_TITLE' => 'Resume Script',
                'TRI_WEBBOT' => '<?php return [];',
            ]],
            'dynaforms' => [[
                'DYN_UID' => 'dyn-resume',
                'DYN_TITLE' => 'Resume Screen',
                'DYN_CONTENT' => json_encode([
                    'items' => [[
                        'type' => 'form',
                        'items' => [[[[
                            'type' => 'text',
                            'variable' => 'field_a',
                            'label' => 'Field A',
                        ]]]],
                    ]],
                ]),
            ]],
        ]);

        $this->assertSame($process->id, $result['process_id']);
        $this->assertSame($processCountBefore, Process::count());
        $this->assertCount(1, $result['scripts']);
        $this->assertCount(1, $result['screens']);
        $this->assertSame('completed', $result['migration_log']['status']);
    }

    public function testApplyMigrationBundleRequiresResumeWhenInterrupted(): void
    {
        $this->actingAs($this->user);

        $proUid = 'interrupted-pro';
        $log = MigrationLog::forProUid($proUid);
        $log->start(['source' => ['pro_uid' => $proUid, 'title' => 'Interrupted']]);
        $log->setProcessId(999);
        $log->completeStep(MigrationLog::STEP_CREATE_PROCESS);
        $log->fail(MigrationLog::STEP_CREATE_SCRIPTS, 'Interrupted');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->writer()->applyMigrationBundle([
            'source' => ['pro_uid' => $proUid, 'title' => 'Interrupted'],
        ]);
    }
}
