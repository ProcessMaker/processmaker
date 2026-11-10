<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * GET /api/1.0/comments-by-case must resolve the case and authorize view on
 * one of its process requests before returning comments. Callers who cannot
 * view the case receive 404 and no comment fields. These tests fail while
 * that check is missing.
 */
class CommentsByCaseAuthorizationTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const ENDPOINT = '/comments-by-case';

    public function testUnauthenticatedRequestIsRejected(): void
    {
        $request = ProcessRequest::factory()->create();
        $comment = $this->commentOn($request, User::factory()->create(), 'MESSAGE');

        $response = $this->getJson('/api/1.0/comments-by-case?case_number=' . $request->case_number);

        $this->assertCommentHidden($response->getContent(), $comment);
        $response->assertUnauthorized();
        $this->assertNotNull(Comment::find($comment->id));
    }

    public function testStrangerCannotReadCommentsByEnumeratingCaseNumbers(): void
    {
        $owner = User::factory()->create();
        $first = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $second = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $firstComment = $this->commentOn($first, $owner, 'MESSAGE');
        $task = ProcessRequestToken::factory()->create([
            'user_id' => $owner->id,
            'process_request_id' => $second->id,
        ]);
        $secondComment = $this->commentOn($task, $owner, 'LOG');
        $stranger = User::factory()->create(['is_administrator' => false]);
        $this->user = $stranger;

        foreach ([$first, $second] as $case) {
            $before = Comment::count();
            $response = $this->apiCall('GET', self::ENDPOINT, [
                'case_number' => $case->case_number,
            ]);
            $this->assertSame($before, Comment::count());
            $this->assertCommentsDenied($response, [$firstComment, $secondComment]);
        }

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $first->case_number,
            'type' => 'MESSAGE',
            'page' => 1,
            'per_page' => 1,
        ]);
        $this->assertCommentsDenied($response, [$firstComment, $secondComment]);
    }

    public function testUserWhoCanViewOneCaseCannotReadAnotherCasesComments(): void
    {
        $attacker = User::factory()->create(['is_administrator' => false]);
        $victimOwner = User::factory()->create(['is_administrator' => false]);
        $visibleCase = ProcessRequest::factory()->create(['user_id' => $attacker->id]);
        $hiddenCase = ProcessRequest::factory()->create(['user_id' => $victimOwner->id]);
        $visibleComment = $this->commentOn($visibleCase, $attacker, 'MESSAGE');
        $hiddenComment = $this->commentOn($hiddenCase, $victimOwner, 'MESSAGE');
        $this->user = $attacker;

        $before = Comment::count();
        $denied = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $hiddenCase->case_number,
        ]);
        $this->assertSame($before, Comment::count());
        $this->assertCommentsDenied($denied, [$hiddenComment]);

        $allowed = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $visibleCase->case_number,
        ]);
        $this->assertCommentVisible($allowed, $visibleComment);
        $this->assertCommentHidden($allowed->getContent(), $hiddenComment);
    }

    public function testUserCannotReadACaseWhenTheyCannotViewAnyOfItsRequests(): void
    {
        $owner = User::factory()->create();
        $parent = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $child = ProcessRequest::factory()->create([
            'user_id' => $owner->id,
            'parent_request_id' => $parent->id,
        ]);
        $child->refresh();
        $this->assertSame($parent->case_number, $child->case_number);

        $parentComment = $this->commentOn($parent, $owner, 'MESSAGE');
        $childComment = $this->commentOn($child, $owner, 'LOG');
        $this->user = User::factory()->create(['is_administrator' => false]);

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $parent->case_number,
        ]);

        $this->assertCommentsDenied($response, [$parentComment, $childComment]);
    }

    public function testCommentsAreNotReturnedWhenTheCaseCannotBeResolved(): void
    {
        $author = User::factory()->create();
        $orphan = Comment::factory()->create([
            'user_id' => $author->id,
            'commentable_id' => ProcessRequest::factory()->create()->id,
            'commentable_type' => ProcessRequest::class,
            'case_number' => 900000001,
            'subject' => 'orphan-subject-' . $this->token(),
            'body' => 'orphan-body-' . $this->token(),
            'hidden' => false,
            'type' => 'MESSAGE',
        ]);
        $this->assertNull(ProcessRequest::where('case_number', 900000001)->first());
        $this->user = User::factory()->create(['is_administrator' => false]);

        $missingCase = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => 900000001,
        ]);
        $this->assertCommentsDenied($missingCase, [$orphan]);

        $unknown = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => 900000002,
        ]);
        $unknown->assertNotFound();
    }

    public function testRequestStarterReceivesOnlyThatCasesComments(): void
    {
        $starter = User::factory()->create(['is_administrator' => false]);
        $own = ProcessRequest::factory()->create(['user_id' => $starter->id]);
        $other = ProcessRequest::factory()->create();
        $ownComment = $this->commentOn($own, $starter, 'MESSAGE');
        $otherComment = $this->commentOn($other, User::factory()->create(), 'MESSAGE');
        $hidden = Comment::factory()->create([
            'user_id' => $starter->id,
            'commentable_id' => $own->id,
            'commentable_type' => ProcessRequest::class,
            'case_number' => $own->case_number,
            'subject' => 'hidden-subject-' . $this->token(),
            'body' => 'hidden-body-' . $this->token(),
            'hidden' => true,
            'type' => 'LOG',
        ]);
        $this->user = $starter;

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $own->case_number,
        ]);

        $this->assertCommentVisible($response, $ownComment);
        $this->assertCommentHidden($response->getContent(), $otherComment);
        $this->assertCommentHidden($response->getContent(), $hidden);
    }

    public function testTaskParticipantReceivesCaseComments(): void
    {
        $participant = User::factory()->create(['is_administrator' => false]);
        $request = ProcessRequest::factory()->create();
        ProcessRequestToken::factory()->create([
            'user_id' => $participant->id,
            'process_request_id' => $request->id,
        ]);
        $comment = $this->commentOn($request, User::factory()->create(), 'MESSAGE');
        $this->user = $participant;

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $request->case_number,
        ]);

        $this->assertCommentVisible($response, $comment);
    }

    public function testChildRequestParticipantReceivesTheSharedCaseComments(): void
    {
        $participant = User::factory()->create(['is_administrator' => false]);
        $parent = ProcessRequest::factory()->create();
        $child = ProcessRequest::factory()->create([
            'parent_request_id' => $parent->id,
        ]);
        $child->refresh();
        ProcessRequestToken::factory()->create([
            'user_id' => $participant->id,
            'process_request_id' => $child->id,
        ]);
        $comment = $this->commentOn($parent, User::factory()->create(), 'MESSAGE');
        $comment->case_number = $parent->case_number;
        $comment->save();
        $this->user = $participant;

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $parent->case_number,
        ]);

        $this->assertCommentVisible($response, $comment);
    }

    public function testProcessManagerReceivesManagedCaseComments(): void
    {
        $manager = User::factory()->create(['is_administrator' => false]);
        $request = ProcessRequest::factory()->create();
        $process = $request->process;
        $process->manager_id = [$manager->id];
        $process->save();
        $comment = $this->commentOn($request, User::factory()->create(), 'LOG');
        $this->user = $manager;

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $request->case_number,
        ]);

        $this->assertCommentVisible($response, $comment);
    }

    public function testUserWithViewAllRequestsReceivesCaseComments(): void
    {
        $reader = User::factory()->create(['is_administrator' => false]);
        $reader->giveDirectPermission('view-all_requests');
        $reader->refresh();
        $request = ProcessRequest::factory()->create();
        $comment = $this->commentOn($request, User::factory()->create(), 'MESSAGE');
        $this->user = $reader;

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $request->case_number,
        ]);

        $this->assertCommentVisible($response, $comment);
    }

    public function testAdministratorReceivesCaseComments(): void
    {
        $request = ProcessRequest::factory()->create();
        $comment = $this->commentOn($request, User::factory()->create(), 'MESSAGE');

        $response = $this->apiCall('GET', self::ENDPOINT, [
            'case_number' => $request->case_number,
        ]);

        $this->assertCommentVisible($response, $comment);
    }

    public function testCommentIndexAndShowStillRequireViewOnTheCommentable(): void
    {
        $owner = User::factory()->create();
        $request = ProcessRequest::factory()->create(['user_id' => $owner->id]);
        $comment = $this->commentOn($request, $owner, 'MESSAGE');
        $this->user = User::factory()->create(['is_administrator' => false]);
        $before = Comment::count();

        $index = $this->apiCall('GET', '/comments', [
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $request->id,
        ]);
        $show = $this->apiCall('GET', '/comments/' . $comment->id);
        $store = $this->apiCall('POST', '/comments', [
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $request->id,
            'subject' => 'unwanted-subject-' . $this->token(),
            'body' => 'unwanted-body-' . $this->token(),
            'hidden' => false,
            'type' => 'MESSAGE',
        ]);

        $index->assertForbidden();
        $show->assertForbidden();
        $store->assertForbidden();
        $this->assertSame($before, Comment::count());
        $this->assertCommentHidden($index->getContent(), $comment);
        $this->assertCommentHidden($show->getContent(), $comment);
    }

    private function commentOn(ProcessRequest|ProcessRequestToken $commentable, User $author, string $type): Comment
    {
        $caseNumber = $commentable instanceof ProcessRequest
            ? $commentable->case_number
            : $commentable->processRequest->case_number;

        return Comment::factory()->create([
            'user_id' => $author->id,
            'commentable_id' => $commentable->id,
            'commentable_type' => $commentable::class,
            'case_number' => $caseNumber,
            'subject' => $type . '-subject-' . $this->token(),
            'body' => $type . '-body-' . $this->token(),
            'hidden' => false,
            'type' => $type,
        ]);
    }

    private function assertCommentsDenied($response, array $comments): void
    {
        $content = $response->getContent();
        foreach ($comments as $comment) {
            $comment->load('user');
            $this->assertCommentHidden($content, $comment);
            foreach ([$comment->user->username, $comment->user->email] as $fragment) {
                $this->assertStringNotContainsString(
                    $fragment,
                    $content,
                    'Comment author was returned without view authorization on the case. Response: ' . $content
                );
            }
        }
        $this->assertSame(404, $response->status(), $content);
    }

    private function assertCommentVisible($response, Comment $comment): void
    {
        $response->assertOk();
        $comment->load('user');
        $rows = collect($response->json('data'));
        $match = $rows->firstWhere('subject', $comment->subject);
        $this->assertNotNull($match, $response->getContent());
        $this->assertSame($comment->body, $match['body']);
        $this->assertSame($comment->type, $match['type']);
        $this->assertSame($comment->case_number, $match['case_number']);
        $this->assertSame($comment->user->id, $match['user']['id']);
        $this->assertSame($comment->user->username, $match['user']['username']);
        $this->assertSame($comment->user->email, $match['user']['email']);
    }

    private function assertCommentHidden(string $content, Comment $comment): void
    {
        $comment->load('user');
        foreach ($this->sensitiveFragments($comment) as $fragment) {
            $this->assertStringNotContainsString(
                $fragment,
                $content,
                'Comment data was returned without view authorization on the case. Response: ' . $content
            );
        }
    }

    /**
     * @return list<string>
     */
    private function sensitiveFragments(Comment $comment): array
    {
        return [
            $comment->subject,
            $comment->body,
        ];
    }

    private function token(): string
    {
        return bin2hex(random_bytes(8));
    }
}
