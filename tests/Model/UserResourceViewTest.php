<?php

namespace Tests\Model;

use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\UserResourceView;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class UserResourceViewTest extends TestCase
{
    use RequestHelper;

    private function createUserResourceView($resource)
    {
        $now = now();
        UserResourceView::factory()->create([
            'user_id' => $this->user->id,
            'viewable_id' => $resource->id,
            'viewable_type' => get_class($resource),
            'created_at' => $now,
        ]);

        return $now;
    }

    public function testAddsUserViewedAtToRequests()
    {
        $processRequest = ProcessRequest::factory()->create();
        $processRequestNotViewed = ProcessRequest::factory()->create();
        $now = $this->createUserResourceView($processRequest);

        $response = $this->apiCall('GET', '/requests');

        $results = collect($response->json()['data']);
        $this->assertEquals(
            (string) $now,
            $results->first(fn ($i) => $i['id'] === $processRequest->id)['user_viewed_at']
        );
        $this->assertNull($results->first(fn ($i) => $i['id'] === $processRequestNotViewed->id)['user_viewed_at']);
    }

    public function testAddsUserViewedAtToTasks()
    {
        $task = ProcessRequestToken::factory()->create();
        $taskNotViewed = ProcessRequestToken::factory()->create();
        $now = $this->createUserResourceView($task);

        $response = $this->apiCall('GET', '/tasks');

        $results = collect($response->json()['data']);
        $this->assertEquals((string) $now, $results->first(fn ($i) => $i['id'] === $task->id)['user_viewed_at']);
        $this->assertNull($results->first(fn ($i) => $i['id'] === $taskNotViewed->id)['user_viewed_at']);
    }
}
