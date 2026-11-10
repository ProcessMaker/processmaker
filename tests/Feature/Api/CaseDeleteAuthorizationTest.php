<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * DELETE /api/1.0/cases/{case_number} must apply the same destroy rule as
 * ProcessRequestPolicy::destroy on the parent process request.
 *
 * These tests fail while any authenticated user can delete another user's case.
 * A fix that authorizes one request and then deletes a different case number,
 * or that loads the case again after authorizing it, still fails.
 */
class CaseDeleteAuthorizationTest extends TestCase
{
    public $withPermissions = true;

    public function testOrdinaryUserCannotDeleteAnotherUsersCaseOrItsFile(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $case = $this->makeCase($owner);
        $other = $this->makeCase(User::factory()->create());

        $this->assertFalse($attacker->can('view', $case['parent']));
        $this->assertFalse($attacker->can('destroy', $case['parent']));
        $this->assertNotSame((int) $case['parent']->case_number, (int) $other['parent']->case_number);

        $response = $this->deleteCase($attacker, $case['parent']->case_number);

        $response->assertForbidden();
        $this->assertCaseIntact($case);
        $this->assertCaseIntact($other);
    }

    public function testSequentialCaseNumbersCanBeTargetedByAnOrdinaryUser(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $first = $this->makeCase($owner);
        $second = $this->makeCase(User::factory()->create());

        $firstNumber = (int) $first['parent']->case_number;
        $secondNumber = (int) $second['parent']->case_number;

        $this->assertGreaterThan(0, $firstNumber);
        $this->assertSame($firstNumber + 1, $secondNumber);

        $response = $this->deleteCase($attacker, $secondNumber);

        $response->assertForbidden();
        $this->assertCaseIntact($first);
        $this->assertCaseIntact($second);
    }

    public function testParticipantWhoCanViewTheCaseCannotDeleteIt(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create();
        $case = $this->makeCase($owner);
        ProcessRequestToken::factory()->create([
            'process_request_id' => $case['parent']->id,
            'process_id' => $case['parent']->process_id,
            'user_id' => $participant->id,
        ]);

        $this->assertTrue($participant->can('view', $case['parent']));
        $this->assertFalse($participant->can('destroy', $case['parent']));

        $response = $this->deleteCase($participant, $case['parent']->case_number);

        $response->assertForbidden();
        $this->assertCaseIntact($case);
    }

    public function testProcessManagerWhoCanViewTheCaseCannotDeleteIt(): void
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $case = $this->makeCase($owner);
        $case['parent']->process->manager_id = [$manager->id];
        $case['parent']->process->save();
        $case['parent']->unsetRelation('process');

        $this->assertTrue($manager->can('view', $case['parent']));
        $this->assertFalse($manager->can('destroy', $case['parent']));

        $response = $this->deleteCase($manager, $case['parent']->case_number);

        $response->assertForbidden();
        $this->assertCaseIntact($case);
    }

    public function testUserWithViewAllRequestsCannotDeleteACaseTheyDoNotOwn(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $viewer->giveDirectPermission('view-all_requests');
        $viewer->refresh();
        $case = $this->makeCase($owner);

        $this->assertTrue($viewer->can('view', $case['parent']));
        $this->assertFalse($viewer->can('destroy', $case['parent']));

        $response = $this->deleteCase($viewer, $case['parent']->case_number);

        $response->assertForbidden();
        $this->assertCaseIntact($case);
    }

    public function testChildRequestOwnerCannotDeleteTheParentCase(): void
    {
        $owner = User::factory()->create();
        $childOwner = User::factory()->create();
        $case = $this->makeCase($owner, $childOwner);

        $this->assertTrue($childOwner->can('destroy', $case['child']));
        $this->assertFalse($childOwner->can('destroy', $case['parent']));

        $response = $this->deleteCase($childOwner, $case['parent']->case_number);

        $response->assertForbidden();
        $this->assertCaseIntact($case);
    }

    public function testDeletingOneCaseNumberDoesNotAuthorizeADifferentCase(): void
    {
        $owner = User::factory()->create();
        $victimOwner = User::factory()->create();
        $owned = $this->makeCase($owner);
        $victim = $this->makeCase($victimOwner);

        $this->assertTrue($owner->can('destroy', $owned['parent']));
        $this->assertFalse($owner->can('destroy', $victim['parent']));

        $response = $this->actingAs($owner, 'api')->json(
            'DELETE',
            '/api/1.0/cases/' . $victim['parent']->case_number,
            ['case_number' => $owned['parent']->case_number]
        );

        $response->assertForbidden();
        $this->assertCaseIntact($owned);
        $this->assertCaseIntact($victim);
    }

    public function testAuthorizedDeleteDoesNotRemoveARequestAttachedAfterTheCaseWasRead(): void
    {
        $owner = User::factory()->create();
        $victimOwner = User::factory()->create();
        $owned = $this->makeCase($owner);
        $victim = $this->makeCase($victimOwner);
        $caseNumber = (int) $owned['parent']->case_number;
        $moved = false;

        DB::listen(function ($query) use (&$moved, $victim, $caseNumber) {
            if ($moved || !$this->isCaseLookup($query->sql, $query->bindings, $caseNumber)) {
                return;
            }

            $moved = true;
            DB::table('process_requests')
                ->where('id', $victim['parent']->id)
                ->update(['case_number' => $caseNumber]);
            DB::table('process_requests')
                ->where('id', $victim['child']->id)
                ->update(['case_number' => $caseNumber]);
        });

        $response = $this->deleteCase($owner, $caseNumber);

        $this->assertTrue($moved, 'The case lookup did not run, so the swap could not observe it.');
        $response->assertNoContent();
        $this->assertDatabaseMissing('process_requests', ['id' => $owned['parent']->id]);
        $this->assertDatabaseMissing('process_requests', ['id' => $owned['child']->id]);
        $this->assertDatabaseHas('process_requests', ['id' => $victim['parent']->id]);
        $this->assertDatabaseHas('process_requests', ['id' => $victim['child']->id]);
        $this->assertDatabaseHas('media', ['id' => $victim['document']->id]);
        $this->assertFileExists($victim['documentPath']);
        $victim['parent']->refresh();
        $this->assertSame($caseNumber, (int) $victim['parent']->case_number);
    }

    public function testParentOwnerCanDeleteTheirOwnCase(): void
    {
        $owner = User::factory()->create();
        $case = $this->makeCase($owner);

        $this->assertTrue($owner->can('destroy', $case['parent']));

        $response = $this->deleteCase($owner, $case['parent']->case_number);

        $response->assertNoContent();
        $this->assertDatabaseMissing('process_requests', ['id' => $case['parent']->id]);
        $this->assertDatabaseMissing('process_requests', ['id' => $case['child']->id]);
        $this->assertDatabaseMissing('media', ['id' => $case['document']->id]);
        $this->assertFileDoesNotExist($case['documentPath']);
    }

    public function testAdministratorCanDeleteACase(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_administrator' => true]);
        $case = $this->makeCase($owner);

        $response = $this->deleteCase($admin, $case['parent']->case_number);

        $response->assertNoContent();
        $this->assertDatabaseMissing('process_requests', ['id' => $case['parent']->id]);
        $this->assertFileDoesNotExist($case['documentPath']);
    }

    public function testGuestCannotDeleteACase(): void
    {
        $owner = User::factory()->create();
        $case = $this->makeCase($owner);

        $response = $this->json('DELETE', '/api/1.0/cases/' . $case['parent']->case_number);

        $response->assertUnauthorized();
        $this->assertCaseIntact($case);
    }

    /**
     * @return array{
     *     parent: ProcessRequest,
     *     child: ProcessRequest,
     *     token: ProcessRequestToken,
     *     comment: Comment,
     *     notification: Notification,
     *     document: \ProcessMaker\Models\Media,
     *     documentPath: string
     * }
     */
    private function makeCase(User $owner, ?User $childOwner = null): array
    {
        $parent = ProcessRequest::factory()->create([
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
        ]);
        $parent->refresh();

        $child = ProcessRequest::factory()->create([
            'user_id' => ($childOwner ?? User::factory()->create())->id,
            'parent_request_id' => $parent->id,
            'process_id' => $parent->process_id,
            'status' => 'ACTIVE',
        ]);
        $child->refresh();

        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $parent->id,
            'process_id' => $parent->process_id,
            'user_id' => $owner->id,
        ]);

        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $parent->id,
            'case_number' => $parent->case_number,
            'body' => 'case comment',
            'subject' => 'case comment',
        ]);

        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => json_encode([
                'type' => 'FILE_SHARED',
                'request_id' => $parent->id,
                'url' => '/requests/' . $parent->id,
            ]),
            'url' => '/requests/' . $parent->id,
        ]);

        $document = $parent->addMediaFromString('case-document')
            ->usingFileName('case-document.txt')
            ->usingName('case-document')
            ->withCustomProperties(['data_name' => 'case_document'])
            ->toMediaCollection('default', 'public');
        $documentPath = $document->getPath();
        $this->assertFileExists($documentPath);

        CaseStarted::factory()->create([
            'case_number' => $parent->case_number,
            'user_id' => $owner->id,
            'case_title' => 'Case ' . $parent->case_number,
            'case_title_formatted' => 'Case ' . $parent->case_number,
        ]);

        return [
            'parent' => $parent,
            'child' => $child,
            'token' => $token,
            'comment' => $comment,
            'notification' => $notification,
            'document' => $document,
            'documentPath' => $documentPath,
        ];
    }

    private function deleteCase(User $user, int|string $caseNumber)
    {
        return $this->actingAs($user, 'api')
            ->json('DELETE', '/api/1.0/cases/' . $caseNumber);
    }

    private function assertCaseIntact(array $case): void
    {
        $this->assertDatabaseHas('process_requests', [
            'id' => $case['parent']->id,
            'case_number' => $case['parent']->case_number,
        ]);
        $this->assertDatabaseHas('process_requests', [
            'id' => $case['child']->id,
            'case_number' => $case['parent']->case_number,
        ]);
        $this->assertDatabaseHas('process_request_tokens', ['id' => $case['token']->id]);
        $this->assertDatabaseHas('comments', [
            'id' => $case['comment']->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('notifications', ['id' => $case['notification']->id]);
        $this->assertDatabaseHas('media', ['id' => $case['document']->id]);
        $this->assertDatabaseHas('case_numbers', ['process_request_id' => $case['parent']->id]);
        $this->assertDatabaseHas('cases_started', ['case_number' => $case['parent']->case_number]);
        $this->assertFileExists($case['documentPath']);
    }

    private function isCaseLookup(string $sql, array $bindings, int $caseNumber): bool
    {
        $normalized = strtolower($sql);
        if (!str_contains($normalized, 'from `process_requests`') && !str_contains($normalized, 'from process_requests')) {
            return false;
        }
        if (!str_contains($normalized, 'case_number')) {
            return false;
        }
        if (!str_starts_with(ltrim($normalized), 'select')) {
            return false;
        }

        foreach ($bindings as $binding) {
            if ((string) $binding === (string) $caseNumber) {
                return true;
            }
        }

        return false;
    }
}
