<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Spec for the default-stages and stage-mapping summaries.
 *
 * These tests fail while any authenticated user can read organization-wide
 * case counts and aggregation sums. They pass when both of the following hold:
 * the caller can view the process in the route, and every count and sum
 * includes only requests that user can see on that same process (the request
 * list's for-user scope, kept inside the route process).
 */
class StageSummaryAuthorizationTest extends TestCase
{
    public $withPermissions = true;

    private Process $process;

    private Process $otherProcess;

    private User $stranger;

    private User $viewer;

    private User $outsider;

    private User $participantOnly;

    private User $viewAllOnly;

    private User $viewAllAndView;

    private User $editor;

    private User $administrator;

    private array $stages;

    /**
     * Requests on $process that forUser would return for $viewer.
     * Hidden requests and the viewer's request on $otherProcess are excluded.
     */
    private int $visibleActiveCount = 3;

    private int $visibleCompletedCount = 0;

    private int $visibleTotalCount = 3;

    private int $visibleStage101Count = 2;

    private int $visibleStage101Sum = 43;

    private int $visibleStage102Count = 1;

    private int $visibleStage102Sum = 7;

    /**
     * Every request on $process, including ones the viewer must not see.
     */
    private int $organizationActiveCount = 7;

    private int $organizationCompletedCount = 2;

    private int $organizationTotalCount = 10;

    private int $organizationStage101Count = 7;

    private int $organizationStage101Sum = 5042;

    private int $organizationStage102Count = 3;

    private int $organizationStage102Sum = 507;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stages = [
            ['id' => 101, 'order' => 1, 'name' => 'Review', 'selected' => false],
            ['id' => 102, 'order' => 2, 'name' => 'Award', 'selected' => false],
        ];

        $this->stranger = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->outsider = User::factory()->create();
        $this->participantOnly = User::factory()->create();
        $this->viewAllOnly = User::factory()->create();
        $this->viewAllAndView = User::factory()->create();
        $this->editor = User::factory()->create();
        $this->administrator = User::factory()->admin()->create();

        $this->viewer->giveDirectPermission('view-processes');
        $this->viewAllOnly->giveDirectPermission('view-all_requests');
        $this->viewAllAndView->giveDirectPermission(['view-processes', 'view-all_requests']);
        $this->editor->giveDirectPermission('view-processes');

        $this->process = $this->makeProcess();
        $this->otherProcess = $this->makeProcess();

        $this->editor->refresh();
        $this->process->usersCanEditData()->sync([
            $this->editor->id => ['method' => 'EDIT_DATA'],
        ]);

        // Hidden from the viewer: other participants' cases on the route process.
        $this->makeRequests($this->process, $this->stranger, 'ACTIVE', 101, '1000', 4);
        $this->makeRequests($this->process, $this->stranger, 'COMPLETED', 102, '250', 2);
        $this->makeRequests($this->process, $this->stranger, 'CANCELED', 101, '999', 1);

        // Visible to the viewer via forUser, and only on the route process.
        $started = $this->makeRequests($this->process, $this->viewer, 'ACTIVE', 102, '7', 1)[0];
        $participated = $this->makeRequests($this->process, $this->stranger, 'ACTIVE', 101, '40', 1)[0];
        $selfService = $this->makeRequests($this->process, $this->stranger, 'ACTIVE', 101, '3', 1)[0];

        ProcessRequestToken::factory()->create([
            'user_id' => $this->viewer->id,
            'process_id' => $this->process->id,
            'process_request_id' => $participated->id,
            'is_self_service' => false,
        ]);
        ProcessRequestToken::factory()->create([
            'user_id' => $this->stranger->id,
            'process_id' => $this->process->id,
            'process_request_id' => $selfService->id,
            'is_self_service' => true,
            'self_service_groups' => [
                'users' => [(string) $this->viewer->id],
                'groups' => [],
            ],
        ]);
        // Participation without view-processes must still be refused.
        ProcessRequestToken::factory()->create([
            'user_id' => $this->participantOnly->id,
            'process_id' => $this->process->id,
            'process_request_id' => $started->id,
            'is_self_service' => false,
        ]);

        // Same viewer started a case on a different process. Its amount must
        // not be added to the route process, including when forUser is applied
        // with OR branches that are not grouped under the route process id.
        $this->makeRequests($this->otherProcess, $this->viewer, 'ACTIVE', 102, '123456', 1);
    }

    public function testOutsiderWithoutViewPermissionReceivesNoStageSummary(): void
    {
        $this->assertSummaryForbidden($this->outsider);
    }

    public function testParticipantWithoutViewPermissionReceivesNoStageSummary(): void
    {
        $this->assertSummaryForbidden($this->participantOnly);
    }

    public function testViewAllRequestsWithoutViewProcessesReceivesNoStageSummary(): void
    {
        $this->assertSummaryForbidden($this->viewAllOnly);
    }

    public function testViewerSeesOnlyRequestsVisibleOnTheRouteProcess(): void
    {
        $this->assertSummaryScopedTo(
            $this->viewer,
            $this->visibleActiveCount,
            $this->visibleCompletedCount,
            $this->visibleTotalCount,
            $this->visibleStage101Count,
            $this->visibleStage101Sum,
            $this->visibleStage102Count,
            $this->visibleStage102Sum
        );
    }

    public function testViewerWithNoVisibleRequestsSeesZeroCountsAndSums(): void
    {
        $zeroViewer = User::factory()->create();
        $zeroViewer->giveDirectPermission('view-processes');

        $this->assertSummaryScopedTo($zeroViewer, 0, 0, 0, 0, 0, 0, 0);
    }

    public function testEditDataUserSeesEveryRequestOnTheAuthorizedProcessOnly(): void
    {
        $this->assertSummaryScopedTo(
            $this->editor,
            $this->organizationActiveCount,
            $this->organizationCompletedCount,
            $this->organizationTotalCount,
            $this->organizationStage101Count,
            $this->organizationStage101Sum,
            $this->organizationStage102Count,
            $this->organizationStage102Sum
        );
    }

    public function testViewAllRequestsWithViewProcessesSeesOrganizationTotalsForTheRouteProcess(): void
    {
        $this->assertSummaryScopedTo(
            $this->viewAllAndView,
            $this->organizationActiveCount,
            $this->organizationCompletedCount,
            $this->organizationTotalCount,
            $this->organizationStage101Count,
            $this->organizationStage101Sum,
            $this->organizationStage102Count,
            $this->organizationStage102Sum
        );
    }

    public function testAdministratorSeesOrganizationTotalsForTheRouteProcess(): void
    {
        $this->assertSummaryScopedTo(
            $this->administrator,
            $this->organizationActiveCount,
            $this->organizationCompletedCount,
            $this->organizationTotalCount,
            $this->organizationStage101Count,
            $this->organizationStage101Sum,
            $this->organizationStage102Count,
            $this->organizationStage102Sum
        );
    }

    private function assertSummaryForbidden(User $user): void
    {
        $leaks = [];
        foreach ($this->summaryUrls() as $url) {
            $response = $this->actingAs($user, 'api')->getJson($url);
            $payload = $response->json();
            $leaked = $response->status() !== 403
                || !is_array($payload)
                || array_key_exists('data', $payload)
                || str_contains($response->getContent(), (string) $this->organizationStage101Sum);
            if ($leaked) {
                $leaks[$url] = $response->status() . ' ' . $response->getContent();
            }
        }

        $this->assertSame([], $leaks);
    }

    private function assertSummaryScopedTo(
        User $user,
        int $activeCount,
        int $completedCount,
        int $totalCount,
        int $stage101Count,
        int $stage101Sum,
        int $stage102Count,
        int $stage102Sum
    ): void {
        $defaultStages = $this->actingAs($user, 'api')->getJson($this->summaryUrls()[0]);
        $defaultStages->assertOk();
        $defaultData = $defaultStages->json('data');

        $this->assertSame($totalCount, (int) $defaultData['total']['agregation_count']);
        $this->assertSame(0, (int) $defaultData['total']['agregation_sum']);

        $inProgress = $this->stageById($defaultData['stages'], 'in_progress');
        $completed = $this->stageById($defaultData['stages'], 'completed');
        $this->assertSame($activeCount, (int) $inProgress['agregation_count']);
        $this->assertSame($completedCount, (int) $completed['agregation_count']);
        $this->assertSame(0, (int) $inProgress['agregation_sum']);
        $this->assertSame(0, (int) $completed['agregation_sum']);

        $stageMapping = $this->actingAs($user, 'api')->getJson($this->summaryUrls()[1]);
        $stageMapping->assertOk();
        $mapped = $stageMapping->json('data');

        $this->assertSame($totalCount, (int) $mapped['total']['agregation_count']);
        $this->assertSame($stage101Sum + $stage102Sum, (int) $mapped['total']['agregation_sum']);

        $review = $this->stageById($mapped['stages'], 101);
        $award = $this->stageById($mapped['stages'], 102);
        $this->assertSame('Review', $review['stage_name']);
        $this->assertSame('Award', $award['stage_name']);
        $this->assertSame($stage101Count, (int) $review['agregation_count']);
        $this->assertSame($stage101Sum, (int) $review['agregation_sum']);
        $this->assertSame($stage102Count, (int) $award['agregation_count']);
        $this->assertSame($stage102Sum, (int) $award['agregation_sum']);
    }

    /**
     * Query parameters name the other process and a different data field.
     * The summary must follow the process authorized by the route.
     *
     * @return array{0: string, 1: string}
     */
    private function summaryUrls(): array
    {
        $decoy = [
            'process_id' => $this->otherProcess->id,
            'aggregation' => 'secret_bonus',
        ];

        return [
            route('api.processes.default-stages', ['process' => $this->process->id] + $decoy),
            route('api.processes.stage-mapping', ['process' => $this->process->id] + $decoy),
        ];
    }

    private function makeProcess(): Process
    {
        return Process::factory()->create([
            'user_id' => $this->stranger->id,
            'status' => 'ACTIVE',
            'stages' => $this->stages,
            'aggregation' => 'amount',
        ]);
    }

    /**
     * @return array<int, ProcessRequest>
     */
    private function makeRequests(
        Process $process,
        User $starter,
        string $status,
        int $stageId,
        string $amount,
        int $count
    ): array {
        $requests = [];
        for ($index = 0; $index < $count; $index++) {
            $requests[] = ProcessRequest::factory()->create([
                'name' => 'Stage summary case',
                'process_id' => $process->id,
                'user_id' => $starter->id,
                'status' => $status,
                'callable_id' => 'stage-summary',
                'last_stage_id' => $stageId,
                'last_stage_name' => $stageId === 101 ? 'Review' : 'Award',
                'data' => [
                    'amount' => $amount,
                    'secret_bonus' => '100000',
                ],
            ]);
        }

        return $requests;
    }

    private function stageById(array $stages, int|string $stageId): array
    {
        foreach ($stages as $stage) {
            if ((string) $stage['stage_id'] === (string) $stageId) {
                return $stage;
            }
        }

        $this->fail('Stage ' . $stageId . ' was not in the summary.');
    }
}
