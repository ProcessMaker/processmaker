<?php

namespace Tests\Resources;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ImportProcess;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskTest extends TestCase
{
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

        // Skipping version lock check until the feature is re-enabled
        // $this->assertEquals('original child description', $json['screen']['nested'][0]['description']);
        // $this->assertEquals('original child2 description', $json['screen']['nested'][1]['description']);
        // $this->assertEquals('original child3 description', $json['screen']['nested'][2]['description']);
    }

    public function testRequestFiles()
    {
        $processRequest = ProcessRequest::factory()->create();
        $file1 = UploadedFile::fake()->create('file1.txt', 1);
        $file2 = UploadedFile::fake()->create('file2.txt', 1);
        $file3 = UploadedFile::fake()->create('file3.txt', 1);
        $media1 = $processRequest->addMedia($file1)->withCustomProperties(['data_name' => 'single'])->toMediaCollection();
        $media2 = $processRequest->addMedia($file2)->withCustomProperties(['data_name' => 'multiple'])->toMediaCollection();
        $media3 = $processRequest->addMedia($file3)->withCustomProperties(['data_name' => 'multiple'])->toMediaCollection();

        $r = $processRequest->requestFiles();
        $this->assertCount(1, $r->single);
        $this->assertEquals(['id' => $media1->id, 'file_name' => 'file1.txt', 'mime_type' => 'application/x-empty'], $r->single[0]);
        $this->assertCount(2, $r->multiple);
        $this->assertEquals(['id' => $media2->id, 'file_name' => 'file2.txt', 'mime_type' => 'application/x-empty'], $r->multiple[0]);
        $this->assertEquals(['id' => $media3->id, 'file_name' => 'file3.txt', 'mime_type' => 'application/x-empty'], $r->multiple[1]);

        // Include token
        $r = $processRequest->requestFiles(true);
        $this->assertEquals($r->single[0]['token'], md5('single' . $media1->id . $media1->created_at));
    }

    public function tearDown() : void
    {
        Carbon::setTestNow(); // reset
    }
}
