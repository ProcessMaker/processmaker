<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Helpers\AbeRecipientSignature;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessTaskAssignment;
use ProcessMaker\Models\User;
use ProcessMaker\Packages\Connectors\Email\Jobs\SendEmail;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;
use ReflectionMethod;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class AbeLargeRecipientGroupTest extends TestCase
{
    use RequestHelper;

    /**
     * Stress a single assigned group with 75+ active members.
     */
    public function testResolveAbeRecipientsForLargeSingleGroupWithinBudget(): void
    {
        Bus::fake([SendEmail::class]);

        $group = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Large Group']);
        $active = $this->createUsersInGroups([$group->id], 75, 'ACTIVE');
        $inactive = $this->createUsersInGroups([$group->id], 10, 'INACTIVE');

        $task = $this->startAbeSelfServiceTask([$group->id]);

        $recipients = $this->resolveAbeRecipients($task);

        $this->assertCount(count($active), $recipients);
        $this->assertCount(count($active), array_unique(array_column($recipients, 'email')));

        $inactiveEmails = User::whereIn('id', $inactive)->pluck('email')->all();
        foreach ($inactiveEmails as $inactiveEmail) {
            $this->assertNotContains($inactiveEmail, array_column($recipients, 'email'));
        }

        Bus::assertDispatchedTimes(SendEmail::class, count($active));
    }

    /**
     * Stress personalized ABE recipient resolution for large overlapping groups.
     * Expected unique recipients: 500 active users
     * (200 A-only + 200 B-only + 100 shared); inactive members excluded.
     */
    public function testResolveAbeRecipientsDedupesLargeOverlappingGroupsWithinBudget(): void
    {
        Bus::fake([SendEmail::class]);

        $groupA = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Group A']);
        $groupB = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Group B']);

        $onlyA = $this->createUsersInGroups([$groupA->id], 200, 'ACTIVE');
        $onlyB = $this->createUsersInGroups([$groupB->id], 200, 'ACTIVE');
        $shared = $this->createUsersInGroups([$groupA->id, $groupB->id], 100, 'ACTIVE');
        $inactive = $this->createUsersInGroups([$groupA->id, $groupB->id], 50, 'INACTIVE');

        $expectedUnique = count($onlyA) + count($onlyB) + count($shared);
        $this->assertSame(500, $expectedUnique);

        $task = $this->startAbeSelfServiceTask([$groupA->id, $groupB->id]);
        $this->assertNotNull(ProcessAbeRequestToken::first());

        $resolveStarted = microtime(true);
        $recipients = $this->resolveAbeRecipients($task);
        $resolveSeconds = microtime(true) - $resolveStarted;

        $this->assertCount($expectedUnique, $recipients);

        $emails = array_column($recipients, 'email');
        $this->assertCount($expectedUnique, array_unique($emails));

        $inactiveEmails = User::whereIn('id', $inactive)->pluck('email')->all();
        foreach ($inactiveEmails as $inactiveEmail) {
            $this->assertNotContains($inactiveEmail, $emails);
        }

        $byUserId = [];
        foreach ($recipients as $recipient) {
            $byUserId[$recipient['user_id']] = $recipient;
        }

        foreach ($onlyA as $userId) {
            $this->assertSame([$groupA->id], array_column($byUserId[$userId]['groups'], 'id'));
            $this->assertSame(['ABE Group A'], array_column($byUserId[$userId]['groups'], 'name'));
        }
        foreach ($onlyB as $userId) {
            $this->assertSame([$groupB->id], array_column($byUserId[$userId]['groups'], 'id'));
        }
        foreach ($shared as $userId) {
            $groupIds = array_column($byUserId[$userId]['groups'], 'id');
            sort($groupIds);
            $this->assertSame([$groupA->id, $groupB->id], $groupIds);
        }

        $tokenAbe = ProcessAbeRequestToken::first()->uuid;
        $signStarted = microtime(true);
        foreach ($recipients as $recipient) {
            $query = AbeRecipientSignature::queryString(
                $tokenAbe,
                $recipient['email'],
                $recipient['user_id'],
                $recipient['groups']
            );
            $this->assertStringContainsString('abe_sig=', $query);
        }
        $signSeconds = microtime(true) - $signStarted;

        $this->assertLessThan(
            10.0,
            $resolveSeconds,
            sprintf('Recipient resolution took %.3fs for %d users', $resolveSeconds, $expectedUnique)
        );
        $this->assertLessThan(
            5.0,
            $signSeconds,
            sprintf('Signing took %.3fs for %d recipients', $signSeconds, $expectedUnique)
        );

        Bus::assertDispatchedTimes(SendEmail::class, $expectedUnique);
    }

    /**
     * @param array<int> $groupIds
     */
    private function startAbeSelfServiceTask(array $groupIds): ProcessRequestToken
    {
        $bpmn = str_replace(
            ['[ASSIGNED_USERS]', '[ASSIGNED_GROUPS]', '&#34;screenEmailRef&#34;:10'],
            ['', implode(',', $groupIds), '&#34;screenEmailRef&#34;:0'],
            file_get_contents(__DIR__ . '/Api/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        foreach ($groupIds as $groupId) {
            ProcessTaskAssignment::factory()->create([
                'process_id' => $process->id,
                'process_task_id' => 'node_2',
                'assignment_id' => $groupId,
                'assignment_type' => Group::class,
            ]);
        }

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->firstOrFail();
        $this->assertEquals(1, $task->is_self_service);

        return $task;
    }

    /**
     * @return array<int, array{email:string,user_id:int,groups:array}>
     */
    private function resolveAbeRecipients(ProcessRequestToken $task): array
    {
        $repository = new TokenRepository(app(ExecutionInstanceRepository::class));
        $method = new ReflectionMethod(TokenRepository::class, 'resolveAbeRecipients');
        $method->setAccessible(true);

        return $method->invoke($repository, $task, null);
    }

    /**
     * @param array<int> $groupIds
     * @return array<int>
     */
    private function createUsersInGroups(array $groupIds, int $count, string $status): array
    {
        $users = User::factory()->count($count)->create([
            'status' => $status,
        ]);

        $now = now()->toDateTimeString();
        $memberRows = [];
        foreach ($users as $user) {
            foreach ($groupIds as $groupId) {
                $memberRows[] = [
                    'group_id' => $groupId,
                    'member_id' => $user->id,
                    'member_type' => User::class,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($memberRows, 500) as $chunk) {
            DB::table('group_members')->insert($chunk);
        }

        return $users->pluck('id')->all();
    }
}
