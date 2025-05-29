<?php

namespace Tests\Unit\ProcessMaker\Models;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ProcessRequestTest extends TestCase
{
    /**
     * Test that getActiveTokens correctly returns active tokens for a process request
     * with and without process_collaboration_id
     */
    public function testGetActiveTokens()
    {
        // Test scenario 1: Process request without collaboration ID
        $process = Process::factory()->create();
        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'process_collaboration_id' => null,
        ]);

        // Create tokens for the request (2 active, 1 completed)
        $activeToken1 = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
        ]);

        $activeToken2 = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'status' => 'ACTIVE',
        ]);

        $completedToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'status' => 'CLOSED',
        ]);

        // Test getActiveTokens for request without collaboration
        $activeTokens = ProcessRequest::getActiveTokens($request);
        $this->assertCount(2, $activeTokens);
        $this->assertContains($activeToken1->id, $activeTokens);
        $this->assertContains($activeToken2->id, $activeTokens);
        $this->assertNotContains($completedToken->id, $activeTokens);

        // Test scenario 2: Process requests with collaboration ID
        $collaborationId = 123456; // Using an integer instead of a string

        // Update the existing request with collaboration ID
        $request->process_collaboration_id = $collaborationId;
        $request->save();

        // Create a second request with the same collaboration ID
        $request2 = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'status' => 'ACTIVE',
            'process_collaboration_id' => $collaborationId,
        ]);

        // Create tokens for the second request (1 active, 1 completed)
        $activeToken3 = ProcessRequestToken::factory()->create([
            'process_request_id' => $request2->id,
            'status' => 'ACTIVE',
        ]);

        $completedToken2 = ProcessRequestToken::factory()->create([
            'process_request_id' => $request2->id,
            'status' => 'CLOSED',
        ]);

        // Test getActiveTokens for request with collaboration
        $collaborationActiveTokens = ProcessRequest::getActiveTokens($request);
        $this->assertCount(3, $collaborationActiveTokens);

        // Should contain all active tokens from both requests with the same collaboration ID
        $this->assertContains($activeToken1->id, $collaborationActiveTokens);
        $this->assertContains($activeToken2->id, $collaborationActiveTokens);
        $this->assertContains($activeToken3->id, $collaborationActiveTokens);

        // Should not contain any completed tokens
        $this->assertNotContains($completedToken->id, $collaborationActiveTokens);
        $this->assertNotContains($completedToken2->id, $collaborationActiveTokens);
    }
}
