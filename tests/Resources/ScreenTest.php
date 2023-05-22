<?php

namespace Tests\Resources;

use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportProcess;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenTest extends TestCase
{
    use RequestHelper;

    public function testScreens()
    {
        $this->be($this->user);

        $content = file_get_contents(
            __DIR__ . '/../Fixtures/nested_screen_process.json'
        );
        $import = ImportProcess::dispatchNow($content);

        $processRequest = WorkflowManager::triggerStartEvent(
            $import->process,
            $import->process->getDefinitions()->getEvent('node_1'),
            []
        );
        $task = $processRequest->tokens()->where('status', 'ACTIVE')->firstOrFail();
        $screen = $task->getScreen();

        $url = route('api.screens.show', [$screen]);
        $result = $this->apiCall('GET', $url);
        $json = $result->json();

        $this->assertCount(4, $json['config'][0]['items']);
        $this->assertEquals('FormNestedScreen', $json['config'][0]['items'][2]['component']);
        $this->assertEquals('FormButton', $json['config'][0]['items'][3]['component']);
    }
}
