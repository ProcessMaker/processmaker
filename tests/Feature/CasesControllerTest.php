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
        $childRequest = ProcessRequest::factory()->create([
            'parent_request_id' => $parentRequest->id,
        ]);

        // Call the view
        $response = $this->get(route('cases.edit', ['case_number' => $parentRequest->case_number]));

        // Check the status
        $response->assertStatus(200);

        // Che the response view
        $response->assertViewHas('request', $childRequest);
        $response->assertViewHas('parentRequest', $parentRequest);
        $response->assertViewHas('requestCount', 2);
        $response->assertViewHas('canCancel');
        $response->assertViewHas('canViewComments');
        $response->assertViewHas('canPrintScreens');
        $response->assertViewHas('isProcessManager');
    }
}
