<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Process;
use ProcessMaker\SanitizeHelper;
use ProcessMaker\Models\ScreenVersion;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Repositories\BpmnDocument;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\ProcessTestingTrait;
use ProcessMaker\Models\ProcessTaskAssignment;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Http\Controllers\Api\ProcessController;

class SanitizeHelperTest extends TestCase
{
    use ProcessTestingTrait, WithFaker, RequestHelper, ResourceAssertionsTrait;

    protected $screen;

    protected $screenVersion;

    protected $process;

    protected $processRequest;

    protected function withUserSetup()
    {
        $this->user->is_administrator = true;
        $this->user->status = 'ACTIVE';
        $this->user->save();
    }

    public function testSingleRichTextSanitization()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_9');
        $data = $this->dataSingleTask();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;
        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['form_text_area_1']);
        $this->assertEquals('Sanitize', $processRequestData['input_1']);
    }

    public function testRichTextSanitizationInsideNestedScreen()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_screen.json');
        $childScreen = $this->screen;
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_screen.json');
        // Configure childId screen to nested screen
        $config = str_replace('"screen":1', '"screen":' . $childScreen->id, json_encode($this->screen->config));
        $this->screen->config = json_decode($config);
        $this->screen->save();
        $this->createProcess('tests/Fixtures/sanitize_single_task_nested.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_3');
        $data = $this->dataSingleTask();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['form_text_area_1']);
        $this->assertEquals('Sanitize', $processRequestData['input_1']);
    }

    public function testSingleRichTextSanitizationInsideLoop()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_inside_loop_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data = $this->dataSingleTaskLoop();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['loop_1.form_text_area_3'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['loop_1'][0]['form_text_area_3']);
        $this->assertEquals('Sanitize', $processRequestData['loop_1'][0]['form_input_1']);
    }

    public function testRichTextSanitizationInsideLoopInsideNestedScreen()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_loop_child_screen.json');
        $childScreen = $this->screen;
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_loop_screen.json');
        // Configure childId screen to nested screen
        $config = str_replace('"screen":1', '"screen":' . $childScreen->id, json_encode($this->screen->config));
        $this->screen->config = json_decode($config);
        $this->screen->save();
        $this->createProcess('tests/Fixtures/sanitize_single_task_nested_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data = $this->dataSingleTaskNestedLoop();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['loop_1.form_text_area_4'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['loop_1'][0]['form_text_area_4']);
        $this->assertEquals('Sanitize', $processRequestData['loop_1'][0]['form_input_1']);
    }

    public function testSingleRichTextTwoPagesSanitization()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_two_pages_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_two_pages.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_3');
        $data = $this->dataSingleTaskTwoPages();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['form_text_area_1', 'form_text_area_2'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Do not sanitize page 2<\/strong><\/p>', $processRequestData['form_text_area_1']);
        $this->assertEquals('<p><strong>Do not sanitize page 1<\/strong><\/p>', $processRequestData['form_text_area_2']);
        $this->assertEquals('Sanitize', $processRequestData['input_1']);
    }

    public function testSingleRichTextSanitizationWithNestedVariableName()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_nested_variable_name_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_9');
        $data = $this->dataSingleTaskNestedVariableName();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', [$task->id, 'status' => 'COMPLETED']);
        $response = $this->apiCall('PUT', $route, ['data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['foo.bar.baz'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;
        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['foo']['bar']['baz']);
        $this->assertEquals('Sanitize', $processRequestData['input_1']);
    }

    public function testSingleRichTextSanitizationSameNameDifferentScope()
    {
        $this->markTestSkipped('FOUR-6653');

        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_same_name_different_scope_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data = $this->dataSingleTaskSameNameDifferentScope();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['loop_1.form_text_area_1'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Do not sanitize<\/strong><\/p>', $processRequestData['loop_1'][0]['form_text_area_1']);
        $this->assertEquals('<p><strong>Do not sanitize 2<\/strong><\/p>', $processRequestData['loop_1'][1]['form_text_area_1']);
        $this->assertEquals('Sanitize', $processRequestData['form_text_area_1']);
    }

    public function testSingleRichTextSanitizationInsideTableAndLoop()
    {
        // Prepare scenario ..
        $this->createScreen('tests/Fixtures/sanitize_single_rich_text_inside_table_and_loop_screen.json');
        $this->createProcess('tests/Fixtures/sanitize_single_task_table_loop.bpmn');
        $this->createProcessRequest();
        $task = $this->createTask($this->process->id, $this->screenVersion->id, $this->processRequest->id, 'node_2');
        $data = $this->dataSingleTaskTableAndLoop();

        // Call api and do sanitization ..
        $route = route('api.tasks.update', $task->id);
        $response = $this->apiCall('PUT', $route, ['status' => 'COMPLETED', 'data' => $data]);
        $this->assertStatus(200, $response);

        // Assert do_not_sanitize was updated successfully ..
        $response->assertJsonFragment([
            'do_not_sanitize' => ['loop_1.form_text_area_3', 'form_text_area_1', 'loop_2.form_text_area_4'],
        ]);

        // Assert data was sanitized or not if rich text ..
        $processRequestData = ProcessRequest::findOrFail($this->processRequest->id)->data;

        $this->assertEquals('<p><strong>Inside loop outside table 1</strong><strong>do not sanitize</strong></p>', $processRequestData['loop_1'][0]['form_text_area_3']);
        $this->assertEquals('<p><strong>Inside loop outside table 2</strong><strong>do not sanitize</strong></p>', $processRequestData['loop_1'][1]['form_text_area_3']);
        $this->assertEquals('<p><strong>Inside table do not sanitize</strong></p>', $processRequestData['form_text_area_1']);
        $this->assertEquals('<p><strong>Inside loop inside table 1 </strong><strong>do not sanitize</strong></p>', $processRequestData['loop_2'][0]['form_text_area_4']);
        $this->assertEquals('<p><strong>Inside loop inside table 2 </strong><strong>do not sanitize</strong></p>', $processRequestData['loop_2'][1]['form_text_area_4']);
    }

    private function createScreen($screenConfigFilePath)
    {
        $this->screen = Screen::factory()->create([
            'config' => json_decode(file_get_contents(base_path($screenConfigFilePath))),
        ]);

        $this->screenVersion = ScreenVersion::factory()->create([
            'screen_id' => $this->screen->id,
            'type' => 'FORM',
            'config' => $this->screen->config,
            'status' => 'ACTIVE',
        ]);
    }

    private function createProcess($processFilePath)
    {
        $bpmn = file_get_contents(base_path($processFilePath));
        $bpmn = str_replace('pm:screenRef="1"', 'pm:screenRef="' . $this->screen->id . '"', $bpmn);
        $this->process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $this->user->id,
        ]);
    }

    private function createProcessRequest()
    {
        $processController = app(ProcessController::class);

        $this->processRequest = ProcessRequest::create([
            'name' => $this->faker->sentence(3),
            'data' => [],
            'status' => 'ACTIVE',
            'callable_id' => 'ProcessId',
            'user_id' => $this->user->id,
            'process_id' => $this->process->getKey(),
            'do_not_sanitize' => $processController->getDoNotSanitizeFields($this->process),
            'process_collaboration_id' => null,
        ]);
    }

    private function createTask($processId, $screenVersionId, $processRequestId, $node)
    {
        $task = ProcessRequestToken::factory()->create([
            'process_id' => $processId,
            'version_type' => 'ProcessMaker\Models\ScreenVersion',
            'version_id' => $screenVersionId,
            'element_id' => $node,
            'element_type' => 'task',
            'status' => 'ACTIVE',
            'process_request_id' => $processRequestId,
        ]);

        return $task;
    }

    private function dataSingleTask()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'form_text_area_1' => "<p><strong>Do not sanitize<\/strong><\/p>",
            'input_1' => "<p><strong>Sanitize<\/strong><\/p>",
        ];
    }

    private function dataSingleTaskNestedLoop()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'loop_1' => [
                [
                    'form_text_area_4' => "<p><strong>Do not sanitize<\/strong><\/p>",
                    'form_input_1' => "<p><strong>Sanitize<\/strong><\/p>",
                ],
            ],
        ];
    }

    private function dataTwoTask()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'form_text_area_1' => "<p><strong>Do not sanitize<\/strong><\/p>",
            'form_text_area_20' => "<p><strong>Do not sanitize<\/strong><\/p>",
            'form_text_area_10' => "<p><strong>Sanitize<\/strong><\/p>",
            'input_1' => "<p><strong>Sanitize<\/strong><\/p>",
        ];
    }

    private function dataSingleTaskLoop()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'loop_1' => [
                [
                    'form_text_area_3' => "<p><strong>Do not sanitize<\/strong><\/p>",
                    'form_input_1' => "<p><strong>Sanitize<\/strong><\/p>",
                ],
            ],
        ];
    }

    private function dataSingleTaskTwoPages()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'form_text_area_1' => "<p><strong>Do not sanitize page 2<\/strong><\/p>",
            'form_text_area_2' => "<p><strong>Do not sanitize page 1<\/strong><\/p>",
            'input_1' => "<p><strong>Sanitize<\/strong><\/p>",
        ];
    }

    private function dataSingleTaskNestedVariableName()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'foo' => [
                'bar' => [
                    'baz' => "<p><strong>Do not sanitize<\/strong><\/p>",
                ],
            ],
            'input_1' => "<p><strong>Sanitize<\/strong><\/p>",
        ];
    }

    private function dataSingleTaskSameNameDifferentScope()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'loop_1' => [
                [
                    'form_text_area_1' => "<p><strong>Do not sanitize<\/strong><\/p>",
                ],
                [
                    'form_text_area_1' => "<p><strong>Do not sanitize 2<\/strong><\/p>",
                ],
            ],
            'form_text_area_1' => "<p><strong>Sanitize<\/strong><\/p>",
        ];
    }

    private function dataSingleTaskTableAndLoop()
    {
        return [
            '_user' => [
                'id' => 1,
            ],
            '_request' => [
                'id' => 1,
            ],
            'loop_1' => [
                [
                    'form_text_area_3' => '<p><strong>Inside loop outside table 1</strong><strong>do not sanitize</strong></p>',
                ],
                [
                    'form_text_area_3' => '<p><strong>Inside loop outside table 2</strong><strong>do not sanitize</strong></p>',
                ],
            ],
            'form_text_area_1' => '<p><strong>Inside table do not sanitize</strong></p>',
            'loop_2' => [
                [
                    'form_text_area_4' => '<p><strong>Inside loop inside table 1 </strong><strong>do not sanitize</strong></p>',
                ],
                [
                    'form_text_area_4' => '<p><strong>Inside loop inside table 2 </strong><strong>do not sanitize</strong></p>',
                ],
            ],
            'form_text_area_2' => '<b>Inside table SANITIZE</b>',
        ];
    }
}
