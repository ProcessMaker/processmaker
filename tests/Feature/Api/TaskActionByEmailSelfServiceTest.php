<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
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

class TaskActionByEmailSelfServiceTest extends TestCase
{
    use RequestHelper;

    public function testActionByEmailCreatesTokenWhenSelfServiceWithGroupAssignment(): void
    {
        $directUser = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'abe-direct@example.com',
        ]);
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        $groupMember = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'abe-group-member@example.com',
        ]);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $groupMember->id,
            'member_type' => User::class,
        ]);

        $bpmn = str_replace(
            ['[ASSIGNED_USERS]', '[ASSIGNED_GROUPS]'],
            [(string) $directUser->id, (string) $group->id],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);

        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $directUser->id,
            'assignment_type' => User::class,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->firstOrFail();
        $this->assertEquals(1, $task->is_self_service);
        $this->assertNull($task->user_id);

        $abeToken = ProcessAbeRequestToken::first();
        $this->assertNotNull($abeToken);
        $this->assertEquals($task->id, $abeToken->process_request_token_id);
    }

    /**
     * A user in two assigned self-service groups must get one ABE email, not one per group.
     */
    public function testOverlappingGroupMembershipSendsOneAbeEmail(): void
    {
        Bus::fake([SendEmail::class]);

        $groupA = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Group A']);
        $groupB = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Group B']);

        $shared = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'shared-abe@example.com',
        ]);
        $onlyA = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'only-a@example.com',
        ]);
        $onlyB = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => 'only-b@example.com',
        ]);

        foreach ([[$groupA->id, $shared->id], [$groupB->id, $shared->id], [$groupA->id, $onlyA->id], [$groupB->id, $onlyB->id]] as [$groupId, $userId]) {
            GroupMember::factory()->create([
                'group_id' => $groupId,
                'member_id' => $userId,
                'member_type' => User::class,
            ]);
        }

        $bpmn = str_replace(
            [
                '[ASSIGNED_USERS]',
                '[ASSIGNED_GROUPS]',
                '&#34;screenEmailRef&#34;:10',
                '&#34;requireLogin&#34;:true',
            ],
            [
                '',
                $groupA->id . ',' . $groupB->id,
                '&#34;screenEmailRef&#34;:0',
                '&#34;requireLogin&#34;:true,&#34;trackResponder&#34;:true',
            ],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $groupA->id,
            'assignment_type' => Group::class,
        ]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $groupB->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->firstOrFail();
        $this->assertEquals(1, $task->is_self_service);

        $repository = new TokenRepository(app(ExecutionInstanceRepository::class));
        $method = new ReflectionMethod(TokenRepository::class, 'resolveAbeRecipients');
        $method->setAccessible(true);
        $recipients = $method->invoke($repository, $task, null);

        $this->assertCount(3, $recipients);
        $emails = array_column($recipients, 'email');
        $this->assertCount(3, array_unique($emails));

        $sharedRecipient = collect($recipients)->firstWhere('user_id', $shared->id);
        $this->assertNotNull($sharedRecipient);
        $this->assertEqualsCanonicalizing(
            [$groupA->id, $groupB->id],
            array_column($sharedRecipient['groups'], 'id')
        );

        Bus::assertDispatchedTimes(SendEmail::class, 3);

        $sharedDispatches = 0;
        Bus::assertDispatched(SendEmail::class, function (SendEmail $job) use (&$sharedDispatches) {
            $config = (function () {
                return $this->config;
            })->call($job);
            $to = $config['addEmails'] ?? $config['email'] ?? '';
            // Personalized path: one recipient address per job (not comma-joined).
            $this->assertStringNotContainsString(',', (string) $to);
            if ($to === 'shared-abe@example.com') {
                $sharedDispatches++;
            }

            return true;
        });
        $this->assertSame(1, $sharedDispatches);
    }

    /**
     * Two different users that share an email receive one personalized ABE send (first user wins).
     */
    public function testDistinctUsersSharingEmailReceiveOneAbeEmail(): void
    {
        Bus::fake([SendEmail::class]);

        $group = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Shared Inbox']);
        $sharedEmail = 'shared-inbox@example.com';

        $userOne = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => $sharedEmail,
        ]);
        $userTwo = User::factory()->create([
            'status' => 'ACTIVE',
            'email' => $sharedEmail,
        ]);

        foreach ([$userOne->id, $userTwo->id] as $userId) {
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'member_id' => $userId,
                'member_type' => User::class,
            ]);
        }

        $bpmn = str_replace(
            [
                '[ASSIGNED_USERS]',
                '[ASSIGNED_GROUPS]',
                '&#34;screenEmailRef&#34;:10',
                '&#34;requireLogin&#34;:true',
            ],
            [
                '',
                (string) $group->id,
                '&#34;screenEmailRef&#34;:0',
                '&#34;requireLogin&#34;:true,&#34;trackResponder&#34;:true',
            ],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        $task = ProcessRequestToken::where('status', 'ACTIVE')->firstOrFail();

        $repository = new TokenRepository(app(ExecutionInstanceRepository::class));
        $method = new ReflectionMethod(TokenRepository::class, 'resolveAbeRecipients');
        $method->setAccessible(true);
        $recipients = $method->invoke($repository, $task, null);

        $this->assertCount(1, $recipients);
        $this->assertSame($sharedEmail, $recipients[0]['email']);
        $this->assertSame($userOne->id, $recipients[0]['user_id']);

        Bus::assertDispatchedTimes(SendEmail::class, 1);
    }

    /**
     * Without trackResponder, ABE sends one shared email (comma-joined To), not one per recipient.
     */
    public function testWithoutTrackResponderSendsOneSharedAbeEmail(): void
    {
        Bus::fake([SendEmail::class]);

        $group = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Legacy Shared']);
        $users = collect([
            User::factory()->create(['status' => 'ACTIVE', 'email' => 'legacy-a@example.com']),
            User::factory()->create(['status' => 'ACTIVE', 'email' => 'legacy-b@example.com']),
            User::factory()->create(['status' => 'ACTIVE', 'email' => 'legacy-c@example.com']),
        ]);

        foreach ($users as $user) {
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'member_id' => $user->id,
                'member_type' => User::class,
            ]);
        }

        $bpmn = str_replace(
            [
                '[ASSIGNED_USERS]',
                '[ASSIGNED_GROUPS]',
                '&#34;screenEmailRef&#34;:10',
            ],
            [
                '',
                (string) $group->id,
                '&#34;screenEmailRef&#34;:0',
            ],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );
        $this->assertStringNotContainsString('trackResponder', $bpmn);

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        Bus::assertDispatchedTimes(SendEmail::class, 1);

        $job = Bus::dispatched(SendEmail::class)->first();
        $config = (function () {
            return $this->config;
        })->call($job);
        $to = (string) ($config['addEmails'] ?? $config['email'] ?? '');

        foreach ($users->pluck('email') as $email) {
            $this->assertStringContainsString($email, $to);
        }
        $this->assertStringContainsString(',', $to);
        $this->assertTrue($job->delay === null || $job->delay === 0);
    }

    /**
     * With trackResponder, personalized sends are staggered by abe_email_stagger_seconds.
     */
    public function testTrackResponderStaggersPersonalizedAbeEmailDispatches(): void
    {
        Bus::fake([SendEmail::class]);

        $now = Carbon::parse('2026-11-13 12:00:00');
        Carbon::setTestNow($now);
        config(['app.abe_email_stagger_seconds' => 3]);

        try {
            $group = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Stagger']);
            $emails = [
                'stagger-a@example.com',
                'stagger-b@example.com',
                'stagger-c@example.com',
            ];
            foreach ($emails as $email) {
                $user = User::factory()->create([
                    'status' => 'ACTIVE',
                    'email' => $email,
                ]);
                GroupMember::factory()->create([
                    'group_id' => $group->id,
                    'member_id' => $user->id,
                    'member_type' => User::class,
                ]);
            }

            $bpmn = str_replace(
                [
                    '[ASSIGNED_USERS]',
                    '[ASSIGNED_GROUPS]',
                    '&#34;screenEmailRef&#34;:10',
                    '&#34;requireLogin&#34;:true',
                ],
                [
                    '',
                    (string) $group->id,
                    '&#34;screenEmailRef&#34;:0',
                    '&#34;requireLogin&#34;:true,&#34;trackResponder&#34;:true',
                ],
                file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
            );

            $process = Process::factory()->create(['bpmn' => $bpmn]);
            ProcessTaskAssignment::factory()->create([
                'process_id' => $process->id,
                'process_task_id' => 'node_2',
                'assignment_id' => $group->id,
                'assignment_type' => Group::class,
            ]);

            $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
            $response = $this->apiCall('POST', $route, []);
            $response->assertStatus(201);

            $jobs = Bus::dispatched(SendEmail::class);
            $this->assertCount(3, $jobs);

            $delaySeconds = $jobs->map(function (SendEmail $job) use ($now) {
                $config = (function () {
                    return $this->config;
                })->call($job);
                $to = (string) ($config['addEmails'] ?? $config['email'] ?? '');
                $this->assertStringNotContainsString(',', $to);

                if ($job->delay === null || $job->delay === 0) {
                    return 0;
                }

                return (int) $now->diffInSeconds(Carbon::parse($job->delay));
            })->sort()->values()->all();

            $this->assertSame([0, 3, 6], $delaySeconds);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Zero stagger still personalizes, but all dispatches run without delay.
     */
    public function testTrackResponderWithZeroStaggerDispatchesWithoutDelay(): void
    {
        Bus::fake([SendEmail::class]);
        config(['app.abe_email_stagger_seconds' => 0]);

        $group = Group::factory()->create(['status' => 'ACTIVE', 'name' => 'ABE Zero Stagger']);
        foreach (['zero-a@example.com', 'zero-b@example.com'] as $email) {
            $user = User::factory()->create(['status' => 'ACTIVE', 'email' => $email]);
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'member_id' => $user->id,
                'member_type' => User::class,
            ]);
        }

        $bpmn = str_replace(
            [
                '[ASSIGNED_USERS]',
                '[ASSIGNED_GROUPS]',
                '&#34;screenEmailRef&#34;:10',
                '&#34;requireLogin&#34;:true',
            ],
            [
                '',
                (string) $group->id,
                '&#34;screenEmailRef&#34;:0',
                '&#34;requireLogin&#34;:true,&#34;trackResponder&#34;:true',
            ],
            file_get_contents(__DIR__ . '/processes/TaskActionByEmailSelfService.bpmn')
        );

        $process = Process::factory()->create(['bpmn' => $bpmn]);
        ProcessTaskAssignment::factory()->create([
            'process_id' => $process->id,
            'process_task_id' => 'node_2',
            'assignment_id' => $group->id,
            'assignment_type' => Group::class,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $response = $this->apiCall('POST', $route, []);
        $response->assertStatus(201);

        Bus::assertDispatchedTimes(SendEmail::class, 2);
        foreach (Bus::dispatched(SendEmail::class) as $job) {
            $this->assertTrue($job->delay === null || $job->delay === 0);
        }
    }
}
