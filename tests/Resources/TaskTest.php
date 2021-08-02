<?php
namespace Tests\Resources;

use Tests\TestCase;
use ProcessMaker\Models\User;
use Illuminate\Support\Carbon;
use ProcessMaker\Models\Screen;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\ProcessRequest;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\ProcessRequestToken;

class TaskTest extends TestCase {

    use RequestHelper;

    public function testScreens()
    {
        $date = Carbon::now();
        $this->be($this->user);

        $content = file_get_contents(
            __DIR__ . '/../Fixtures/nested_screen_process.json'
        );
        $import = ImportProcess::dispatchNow($content);

        $parent = Screen::where('title', 'parent')->orderBy('id', 'desc')->firstOrFail();
        $child = Screen::where('title', 'child')->orderBy('id', 'desc')->firstOrFail();
        $child2 = Screen::where('title', 'child2')->orderBy('id', 'desc')->firstOrFail();
        $child3 = Screen::where('title', 'child3')->orderBy('id', 'desc')->firstOrFail();

        $parent->update(['description' => 'original parent description']);
        $child->update(['description' => 'original child description']);
        $child2->update(['description' => 'original child2 description']);
        $child3->update(['description' => 'original child3 description']);

        Carbon::setTestNow($date->addDays(1));

        $processRequest = WorkflowManager::triggerStartEvent(
            $import->process,
            $import->process->getDefinitions()->getEvent('node_1'),
            []
        );
        
        Carbon::setTestNow($date->addDays(2));
        
        // Update description after the request is created
        $parent->update(['description' => 'new parent description']);
        $child->update(['description' => 'new child description']);
        $child2->update(['description' => 'new child2 description']);
        $child3->update(['description' => 'new child3 description']);

        $task = $processRequest->tokens()->where('status', 'ACTIVE')->firstOrFail();

        $url = route('api.tasks.show', [$task]);
        $result = $this->apiCall('GET', $url, ['include'=>'screen,nested']);
        $json = $result->json();

        $this->assertEquals('parent', $json['screen']['title']);
        $this->assertCount(3, $json['screen']['nested']);
        $this->assertEquals('child', $json['screen']['nested'][0]['title']);
        
        $this->assertEquals('original child description', $json['screen']['nested'][0]['description']);
        $this->assertEquals('original child2 description', $json['screen']['nested'][1]['description']);
        $this->assertEquals('original child3 description', $json['screen']['nested'][2]['description']);
    }

    public function tearDown() : void {
        Carbon::setTestNow(); // reset
    }
}