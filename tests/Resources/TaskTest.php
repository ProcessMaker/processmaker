<?php
namespace Tests\Resources;

use Tests\TestCase;
use ProcessMaker\Models\User;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;

class TaskTest extends TestCase {

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

        $url = route('api.tasks.show', [$task]);
        $result = $this->apiCall('GET', $url, ['include'=>'allScreens']);
        $screens = $result->json()['screens'];

        $this->assertCount(2, $screens);
        $this->assertEquals('main', $screens[0]['type']);
        $this->assertEquals('nested', $screens[1]['type']);
    }
}