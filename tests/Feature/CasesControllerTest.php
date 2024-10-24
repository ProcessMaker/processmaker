<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class CasesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShowCaseWithUserWithoutParticipation()
    {
        // Create user admin
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(403);
    }

    public function testShowCaseWithUserAdmin()
    {
        // Create user admin
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);
    }

    public function testShowCaseWithParticipateUser()
    {
        // Create user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create the main request
        $parentRequest = ProcessRequest::factory()->create([
            'parent_request_id' => null,
        ]);

        // Create request child
        ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Create the participation
        ProcessRequestToken::factory()->create([
            'process_request_id' => $parentRequest->id,
            'user_id' => $user->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);
    }
}
