<?php

namespace Tests\Feature\Screens;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScreenCacheTest extends TestCase
{
    use RequestHelper;

    public function testScreenCache()
    {
        // 1. Import a process with a screen with a nested without screen selected
        $process = $this->createProcessFromJSON(
            base_path('tests/Fixtures/processes/screen_process_v1.json'),
            [
                'name' => 'Screen Process Test',
                'status' => 'ACTIVE'
            ]
        );

        // Run the process (trigger start event)
        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();
        $request = ProcessRequest::find($requestJson['id']);

        // Get the current active task for the process request
        $task = ProcessRequestToken::where([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
            'element_type' => 'task',
        ])->firstOrFail();

        // Get the screen of the process using the route api.1.1.show.screen
        $response = $this->getJson(route('api.1.1.tasks.show.screen', ['taskId' => $task->id, 'include' => 'screen,nested']));

        // Add your test assertions here
        $response->assertStatus(200);
        // Save the latest version id of the screen
        $screenId = $response->json()['screen_id'];
        $latestScreenVersionId = Screen::find($screenId)->getLatestVersion()->id;
        $this->assertCount(2, $response->json()['config'][0]['items'], 'The screen imported first time should have 2 items');
        // Verify label of the first item
        $this->assertEquals('Line Input v1', $response->json()['config'][0]['items'][0]['label'], 'The label of the first item should be "Line Input v1"');

        // 2. Import a process with a screen with a nested pointing to an empty screen (config = null in the database)
        $process = $this->createProcessFromJSON(
            base_path('tests/Fixtures/processes/screen_process_v2.json'),
            [
                'name' => 'Screen Process Test',
                'status' => 'ACTIVE'
            ]
        );

        // Get the screen of the process using the route api.1.1.show.screen
        $response = $this->getJson(route('api.1.1.tasks.show.screen', ['taskId' => $task->id, 'include' => 'screen,nested']));

        // Add your test assertions here
        $response->assertStatus(200);
        // There is nested screen with an empty config
        $this->assertCount(1, $response->json()['nested'], 'The screen imported first time should have 1 nested screen');
        $this->assertCount(0, $response->json()['nested'][0]['config'], 'The nested screen should have an empty config');
        // Verify label of the first item was updated
        $this->assertEquals('Line Input v2', $response->json()['config'][0]['items'][0]['label'], 'The label of the first item should be "Line Input v2"');
        // Verify the rendered screen version is newer than the latest version id
        $currentScreenVersionId = Screen::find($screenId)->getLatestVersion()->id;
        $this->assertGreaterThan($latestScreenVersionId, $currentScreenVersionId, 'The current screen version should be newer than the latest version id');
        $latestScreenVersionId = $currentScreenVersionId;

        // 3. Import a process with a screen with a nested pointing to a screen with one item
        $process = $this->createProcessFromJSON(
            base_path('tests/Fixtures/processes/screen_process_v3.json'),
            [
                'name' => 'Screen Process Test',
                'status' => 'ACTIVE'
            ]
        );

        // Get the screen of the process using the route api.1.1.show.screen
        $response = $this->getJson(route('api.1.1.tasks.show.screen', ['taskId' => $task->id, 'include' => 'screen,nested']));

        // Add your test assertions here
        $response->assertStatus(200);
        // There is nested screen with 1 item
        $this->assertCount(1, $response->json()['nested'], 'The screen imported first time should have 1 nested screen');
        $this->assertCount(1, $response->json()['nested'][0]['config'][0]['items'], 'The nested screen should have 1 item');
        // Verify the rendered screen version is newer than the latest version id
        $currentScreenVersionId = Screen::find($screenId)->getLatestVersion()->id;
        $this->assertGreaterThan($latestScreenVersionId, $currentScreenVersionId, 'The current screen version should be newer than the latest version id');
        $latestScreenVersionId = $currentScreenVersionId;

        // 4. Import a process that updates the nested screen
        $process = $this->createProcessFromJSON(
            base_path('tests/Fixtures/processes/screen_process_v4.json'),
            [
                'name' => 'Screen Process Test',
                'status' => 'ACTIVE'
            ]
        );

        // Get the screen of the process using the route api.1.1.show.screen
        $response = $this->getJson(route('api.1.1.tasks.show.screen', ['taskId' => $task->id, 'include' => 'screen,nested']));

        // Add your test assertions here
        $response->assertStatus(200);
        // There is nested screen with 1 item
        $this->assertCount(1, $response->json()['nested'], 'The screen imported first time should have 1 nested screen');
        $this->assertCount(1, $response->json()['nested'][0]['config'][0]['items'], 'The nested screen should have 1 item');
        // Verify the rendered screen version is the same
        $currentScreenVersionId = Screen::find($screenId)->getLatestVersion()->id;
        $this->assertGreaterThan($latestScreenVersionId, $currentScreenVersionId, 'The current screen version should be newer than the latest version id');
        // Verify the nested screen was updated
        $this->assertEquals('New Textarea v4', $response->json()['nested'][0]['config'][0]['items'][0]['config']['label'], 'The nested screen should have the new label "New Textarea v4"');

        // 4. Import a process that updates the nested screen
        $process = $this->createProcessFromJSON(
            base_path('tests/Fixtures/processes/screen_process_v4.json'),
            [
                'name' => 'Screen Process Test',
                'status' => 'ACTIVE'
            ]
        );

        // Get the screen of the process using the route api.1.1.show.screen
        $response = $this->getJson(route('api.1.1.tasks.show.screen', ['taskId' => $task->id, 'include' => 'screen,nested']));

        // Add your test assertions here
        $response->assertStatus(200);
        // There is nested screen with 1 item
        $this->assertCount(1, $response->json()['nested'], 'The screen imported first time should have 1 nested screen');
        $this->assertCount(1, $response->json()['nested'][0]['config'][0]['items'], 'The nested screen should have 1 item');
        // Verify the rendered screen version is the same
        $currentScreenVersionId = Screen::find($screenId)->getLatestVersion()->id;
        $this->assertGreaterThan($latestScreenVersionId, $currentScreenVersionId, 'The current screen version should be newer than the latest version id');
        // Verify the nested screen was updated
        $this->assertEquals('New Textarea v4', $response->json()['nested'][0]['config'][0]['items'][0]['config']['label'], 'The nested screen should have the new label "New Textarea v4"');
    }
}
