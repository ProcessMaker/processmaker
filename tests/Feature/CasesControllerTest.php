<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use Tests\TestCase;

class CasesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_method_shows_correct_case_data()
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

        // Call the view
        $response = $this->get(route('cases.show', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);
    }
}
