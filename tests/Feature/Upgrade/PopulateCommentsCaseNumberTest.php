<?php

namespace Tests\Feature\Upgrade;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class PopulateCommentsCaseNumberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test populateCommentsCaseNumber method.
     *
     * @return void
     */
    public function test_populate_comments_case_number()
    {
        // Create ProcessRequest and ProcessRequestToken
        $processRequest = ProcessRequest::factory()->create();
        $processRequestToken = ProcessRequestToken::factory()->create([
            'process_request_id' => $processRequest->id,
        ]);

        // Create comment with case_number = null
        $comment1 = Comment::factory()->create([
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $processRequest->id,
            'case_number' => null,
        ]);

        $comment2 = Comment::factory()->create([
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $processRequestToken->id,
            'case_number' => null,
        ]);

        // Create comment with case_number = value
        $comment3 = Comment::factory()->create([
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $processRequest->id,
            'case_number' => $processRequest->case_number,
        ]);

        // Execute the command
        $this->artisan('processmaker:update-comments-case-number');

        // Refresh the comments
        $comment1->refresh();
        $comment2->refresh();
        $comment3->refresh();

        // Check that all case_number is not null
        $this->assertNotNull($comment1->case_number);
        $this->assertNotNull($comment2->case_number);
        $this->assertNotNull($comment3->case_number);
        // Check that all case_number has the correct value
        $this->assertEquals($processRequest->case_number, $comment1->case_number);
        $this->assertEquals($processRequest->case_number, $comment2->case_number);
        $this->assertEquals($processRequest->case_number, $comment3->case_number);
    }
}
