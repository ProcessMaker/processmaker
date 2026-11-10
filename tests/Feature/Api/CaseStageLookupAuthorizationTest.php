<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Spec for case lookups that return another account's case without a view check.
 *
 * These tests fail while that disclosure (and the case delete that uses the same
 * unchecked case number) is present. They pass once each operation authorizes
 * the same process request it reads or deletes.
 */
class CaseStageLookupAuthorizationTest extends TestCase
{
    use RequestHelper;

    public function testStageBarRequiresAuthentication(): void
    {
        $case = $this->makeCase('VictimStageUnauthenticated', '2019-03-17 04:05:06');

        $response = $this->getJson('/api/1.0/cases/stages_bar/' . $case['parent']->case_number);

        $response->assertUnauthorized();
        $response->assertDontSee('VictimStageUnauthenticated', false);
    }

    public function testOutsiderCannotReadAnotherCasesStageBar(): void
    {
        $victim = $this->makeCase('VictimStageLookupMarker', '2019-03-17 04:05:06');
        $outsider = User::factory()->create(['is_administrator' => false]);
        $this->user = $outsider;

        $response = $this->apiCall('GET', '/cases/stages_bar/' . $victim['parent']->case_number);

        $response->assertForbidden();
        $this->assertResponseHidesCase($response, $victim);
    }

    public function testOutsiderWhoCanViewTheirOwnCaseCannotReadAnotherCasesStageBar(): void
    {
        $victim = $this->makeCase('VictimStageOtherCaseMarker', '2019-03-17 04:05:06');
        $owned = $this->makeCase('OwnedStageOtherCaseMarker', '2018-02-16 03:04:05');
        $this->user = $owned['participant'];

        $response = $this->apiCall('GET', '/cases/stages_bar/' . $victim['parent']->case_number);

        $response->assertForbidden();
        $this->assertResponseHidesCase($response, $victim);
        $response->assertDontSee('OwnedStageOtherCaseMarker', false);
    }

    public function testStageBarReturnsTheAuthorizedRequestWhenTheCaseNumberIsReassigned(): void
    {
        $victim = $this->makeCase('VictimStageReassignMarker', '2019-03-17 04:05:06');
        $allowed = $this->makeCase('AllowedStageReassignMarker', '2018-02-16 03:04:05');
        $this->user = $allowed['participant'];
        $this->reassignCaseNumberAfterFirstLookup(
            (int) $allowed['parent']->case_number,
            (int) $victim['parent']->case_number,
            910000001
        );

        $response = $this->apiCall('GET', '/cases/stages_bar/' . $allowed['parent']->case_number);

        $response->assertOk();
        $response->assertJsonPath('parentRequest.id', $allowed['parent']->id);
        $response->assertJsonPath('parentRequest.status', $allowed['parent']->status);
        $response->assertJsonPath('parentRequest.case_number', $allowed['parent']->case_number);
        $response->assertSee('AllowedStageReassignMarker', false);
        $this->assertResponseHidesCase($response, $victim);
    }

    public function testParticipantCanReadStageBarForTheirCase(): void
    {
        $case = $this->makeCase('ParticipantStageLookupMarker', '2018-02-16 03:04:05');
        $this->user = $case['participant'];

        $response = $this->apiCall('GET', '/cases/stages_bar/' . $case['parent']->case_number);

        $response->assertOk();
        $response->assertJsonPath('parentRequest.id', $case['parent']->id);
        $response->assertJsonPath('parentRequest.status', 'ACTIVE');
        $response->assertJsonPath('parentRequest.case_number', $case['parent']->case_number);
        $response->assertJsonPath('requestCount', 2);
        $response->assertSee('ParticipantStageLookupMarker', false);
        $response->assertSee('2018-02-16', false);
    }

    public function testProcessManagerCanReadStageBarForManagedCase(): void
    {
        $manager = User::factory()->create(['is_administrator' => false]);
        $case = $this->makeCase('ManagerStageLookupMarker', '2018-02-16 03:04:05', $manager);
        $this->user = $manager;

        $response = $this->apiCall('GET', '/cases/stages_bar/' . $case['parent']->case_number);

        $response->assertOk();
        $response->assertJsonPath('parentRequest.id', $case['parent']->id);
        $response->assertSee('ManagerStageLookupMarker', false);
    }

    public function testOutsiderCannotReadParentRequestByCaseNumber(): void
    {
        $victim = $this->makeCase('VictimParentLookupMarker', '2019-03-17 04:05:06', null, 'VictimParentTitleLookupMarker');
        $this->user = User::factory()->create(['is_administrator' => false]);

        $response = $this->apiCall('GET', '/parent-request-by-case', [
            'case_number' => $victim['parent']->case_number,
        ]);

        $response->assertForbidden();
        $this->assertResponseHidesCase($response, $victim);
        $response->assertDontSee('VictimParentTitleLookupMarker', false);
    }

    public function testParentRequestByCaseReturnsTheAuthorizedRequestWhenTheCaseNumberIsReassigned(): void
    {
        $victim = $this->makeCase('VictimParentReassignMarker', '2019-03-17 04:05:06', null, 'VictimParentReassignTitle');
        $allowed = $this->makeCase('AllowedParentReassignMarker', '2018-02-16 03:04:05', null, 'AllowedParentReassignTitle');
        $this->user = $allowed['owner'];
        $this->reassignCaseNumberAfterFirstLookup(
            (int) $allowed['parent']->case_number,
            (int) $victim['parent']->case_number,
            910000002
        );

        $response = $this->apiCall('GET', '/parent-request-by-case', [
            'case_number' => $allowed['parent']->case_number,
        ]);

        $response->assertOk();
        $response->assertJsonPath('id', $allowed['parent']->id);
        $response->assertJsonPath('status', $allowed['parent']->status);
        $response->assertJsonPath('case_number', $allowed['parent']->case_number);
        $response->assertSee('AllowedParentReassignTitle', false);
        $this->assertResponseHidesCase($response, $victim);
        $response->assertDontSee('VictimParentReassignTitle', false);
    }

    public function testRequesterCanReadParentRequestByCaseNumber(): void
    {
        $case = $this->makeCase('RequesterParentLookupMarker', '2018-02-16 03:04:05', null, 'RequesterParentTitleLookupMarker');
        $this->user = $case['owner'];

        $response = $this->apiCall('GET', '/parent-request-by-case', [
            'case_number' => $case['parent']->case_number,
        ]);

        $response->assertOk();
        $response->assertJsonPath('id', $case['parent']->id);
        $response->assertJsonPath('case_number', $case['parent']->case_number);
        $response->assertJsonPath('status', 'ACTIVE');
        $response->assertSee('RequesterParentTitleLookupMarker', false);
    }

    public function testOutsiderCannotReadCommentsByCaseNumber(): void
    {
        $victim = $this->makeCase('VictimCommentStageMarker', '2019-03-17 04:05:06');
        $this->user = User::factory()->create(['is_administrator' => false]);

        $response = $this->apiCall('GET', '/comments-by-case', [
            'case_number' => $victim['parent']->case_number,
        ]);

        $response->assertForbidden();
        $response->assertDontSee($victim['comment'], false);
        $this->assertResponseHidesCase($response, $victim);
    }

    public function testCommentsByCaseReturnTheAuthorizedRequestsCommentsWhenTheCaseNumberIsReassigned(): void
    {
        $victim = $this->makeCase('VictimCommentReassignMarker', '2019-03-17 04:05:06');
        $allowed = $this->makeCase('AllowedCommentReassignMarker', '2018-02-16 03:04:05');
        $this->user = $allowed['participant'];
        $this->reassignCaseNumberAfterFirstLookup(
            (int) $allowed['parent']->case_number,
            (int) $victim['parent']->case_number,
            910000003
        );

        $response = $this->apiCall('GET', '/comments-by-case', [
            'case_number' => $allowed['parent']->case_number,
        ]);

        $response->assertOk();
        $response->assertSee($allowed['comment'], false);
        $response->assertDontSee($victim['comment'], false);
        $this->assertResponseHidesCase($response, $victim);
    }

    public function testParticipantCanReadCommentsByCaseNumber(): void
    {
        $case = $this->makeCase('ParticipantCommentStageMarker', '2018-02-16 03:04:05');
        $this->user = $case['participant'];

        $response = $this->apiCall('GET', '/comments-by-case', [
            'case_number' => $case['parent']->case_number,
        ]);

        $response->assertOk();
        $response->assertSee($case['comment'], false);
    }

    public function testOutsiderCannotDeleteAnotherCase(): void
    {
        $victim = $this->makeCase('VictimDeleteStageMarker', '2019-03-17 04:05:06');
        $this->user = User::factory()->create(['is_administrator' => false]);

        $response = $this->apiCall('DELETE', '/cases/' . $victim['parent']->case_number);

        $response->assertForbidden();
        $this->assertDatabaseHas('process_requests', ['id' => $victim['parent']->id]);
        $this->assertDatabaseHas('process_requests', ['id' => $victim['child']->id]);
        $this->assertDatabaseHas('comments', ['id' => $victim['commentId']]);
    }

    public function testDeleteRemovesOnlyTheAuthorizedCaseWhenTheCaseNumberIsReassigned(): void
    {
        $victim = $this->makeCase('VictimDeleteReassignMarker', '2019-03-17 04:05:06');
        $allowed = $this->makeCase('AllowedDeleteReassignMarker', '2018-02-16 03:04:05');
        $this->user = $allowed['owner'];
        $this->reassignCaseNumberAfterFirstLookup(
            (int) $allowed['parent']->case_number,
            (int) $victim['parent']->case_number,
            910000004
        );

        $response = $this->apiCall('DELETE', '/cases/' . $allowed['parent']->case_number);

        $response->assertNoContent();
        $this->assertDatabaseMissing('process_requests', ['id' => $allowed['parent']->id]);
        $this->assertDatabaseMissing('process_requests', ['id' => $allowed['child']->id]);
        $this->assertDatabaseHas('process_requests', ['id' => $victim['parent']->id]);
        $this->assertDatabaseHas('process_requests', ['id' => $victim['child']->id]);
        $this->assertDatabaseHas('comments', ['id' => $victim['commentId']]);
    }

    public function testRequesterCanDeleteTheirOwnCase(): void
    {
        $case = $this->makeCase('RequesterDeleteStageMarker', '2018-02-16 03:04:05');
        $this->user = $case['owner'];

        $response = $this->apiCall('DELETE', '/cases/' . $case['parent']->case_number);

        $response->assertNoContent();
        $this->assertDatabaseMissing('process_requests', ['id' => $case['parent']->id]);
    }

    public function testRequestsByCaseDoesNotReturnAnotherUsersRequest(): void
    {
        $victim = $this->makeCase('VictimRequestsByCaseMarker', '2019-03-17 04:05:06', null, 'VictimRequestsByCaseTitle');
        $owned = $this->makeCase('OwnedRequestsByCaseMarker', '2018-02-16 03:04:05');
        $this->user = $owned['participant'];

        $response = $this->apiCall('GET', '/requests-by-case', [
            'case_number' => $victim['parent']->case_number,
        ]);

        $response->assertOk();
        $this->assertResponseHidesCase($response, $victim);
        $response->assertDontSee('VictimRequestsByCaseTitle', false);
    }

    public function testTasksByCaseDoesNotReturnAnotherUsersTask(): void
    {
        $victim = $this->makeCase('VictimTasksByCaseMarker', '2019-03-17 04:05:06');
        $owned = $this->makeCase('OwnedTasksByCaseMarker', '2018-02-16 03:04:05');
        $this->user = $owned['participant'];

        $response = $this->apiCall('GET', '/tasks-by-case', [
            'case_number' => $victim['parent']->case_number,
            'status' => 'CLOSED',
        ]);

        $response->assertOk();
        $response->assertDontSee('VictimTasksByCaseMarker', false);
        $this->assertStringNotContainsString((string) $victim['token']->id, (string) json_encode($response->json('data')));
    }

    /**
     * @return array{parent: ProcessRequest, child: ProcessRequest, owner: User, participant: User, token: ProcessRequestToken, comment: string, commentId: int}
     */
    private function makeCase(string $stageName, string $completedAt, ?User $manager = null, ?string $caseTitle = null): array
    {
        $owner = User::factory()->create(['is_administrator' => false]);
        $participant = User::factory()->create(['is_administrator' => false]);
        $process = Process::factory()->create([
            'status' => 'ACTIVE',
            'stages' => [
                ['id' => 41, 'name' => $stageName, 'order' => 1],
            ],
            'manager_id' => $manager ? [$manager->id] : [],
        ]);

        $parent = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
            'completed_at' => $completedAt,
            'parent_request_id' => null,
        ]);
        $child = ProcessRequest::factory()->create([
            'process_id' => $process->id,
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
            'parent_request_id' => $parent->id,
        ]);
        $parent->refresh();
        $child->refresh();

        if ($caseTitle !== null) {
            ProcessRequest::query()->where('case_number', $parent->case_number)->update([
                'case_title' => $caseTitle,
                'case_title_formatted' => $caseTitle,
            ]);
            $parent->refresh();
        }

        $token = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $parent->id,
            'user_id' => $participant->id,
            'status' => 'CLOSED',
            'element_type' => 'task',
            'element_name' => $stageName,
            'stage_id' => 41,
            'stage_name' => $stageName,
            'completed_at' => $completedAt,
        ]);

        $commentBody = $stageName . '-comment-body';
        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
            'commentable_id' => $parent->id,
            'commentable_type' => ProcessRequest::class,
            'subject' => $stageName . ' subject',
            'body' => $commentBody,
            'hidden' => false,
            'type' => 'MESSAGE',
            'case_number' => $parent->case_number,
        ]);

        return [
            'parent' => $parent,
            'child' => $child,
            'owner' => $owner,
            'participant' => $participant,
            'token' => $token,
            'comment' => $commentBody,
            'commentId' => $comment->id,
            'stage' => $stageName,
            'completedOn' => substr($completedAt, 0, 10),
        ];
    }

    private function reassignCaseNumberAfterFirstLookup(int $requestedCaseNumber, int $otherCaseNumber, int $decoyCaseNumber): void
    {
        $swapped = false;
        DB::listen(function ($query) use (&$swapped, $requestedCaseNumber, $otherCaseNumber, $decoyCaseNumber) {
            if ($swapped || !str_starts_with(ltrim(strtolower($query->sql)), 'select')) {
                return;
            }
            $sql = strtolower($query->sql);
            if (!str_contains($sql, 'process_requests') || !str_contains($sql, 'case_number')) {
                return;
            }
            $bindings = array_map(static fn ($binding) => (string) $binding, $query->bindings);
            if (!in_array((string) $requestedCaseNumber, $bindings, true)) {
                return;
            }

            $swapped = true;
            ProcessRequest::query()->where('case_number', $requestedCaseNumber)->update([
                'case_number' => $decoyCaseNumber,
            ]);
            ProcessRequest::query()->where('case_number', $otherCaseNumber)->update([
                'case_number' => $requestedCaseNumber,
            ]);
            Comment::query()->where('case_number', $requestedCaseNumber)->update([
                'case_number' => $decoyCaseNumber,
            ]);
            Comment::query()->where('case_number', $otherCaseNumber)->update([
                'case_number' => $requestedCaseNumber,
            ]);
        });
    }

    private function assertResponseHidesCase($response, array $case): void
    {
        $content = $response->getContent();
        $this->assertStringNotContainsString($case['comment'], $content);
        $this->assertStringNotContainsString($case['stage'], $content);
        $this->assertStringNotContainsString($case['completedOn'], $content);
        $this->assertSame([], $this->matchingIds($response->json(), [
            $case['parent']->id,
            $case['child']->id,
            $case['token']->id,
            $case['commentId'],
        ]));
        $this->assertSame([], $this->matchingValues($response->json(), 'case_number', [
            $case['parent']->case_number,
            (string) $case['parent']->case_number,
        ]));
    }

    private function matchingIds(mixed $payload, array $ids): array
    {
        return $this->matchingValues($payload, 'id', $ids);
    }

    private function matchingValues(mixed $payload, string $key, array $needles): array
    {
        $found = [];
        $walk = function ($node) use (&$walk, &$found, $key, $needles) {
            if (!is_array($node)) {
                return;
            }
            foreach ($node as $childKey => $value) {
                if ($childKey === $key && in_array($value, $needles, true)) {
                    $found[] = $value;
                }
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };
        $walk(is_array($payload) ? $payload : []);

        return $found;
    }
}
