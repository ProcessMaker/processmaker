<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ProcessMaker\Models\CaseNumber;
use ProcessMaker\Models\CaseParticipated;
use ProcessMaker\Models\CaseStarted;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\Media;
use ProcessMaker\Models\Notification;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestLock;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Models\TaskDraft;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CaseDeleteTest extends TestCase
{
    use RequestHelper;

    public function testDeleteCaseRemovesCoreRecords(): void
    {
        $caseNumber = 12345;
        $requests = ProcessRequest::factory()
            ->count(2)
            ->withCaseNumber($caseNumber)
            ->create();

        CaseNumber::factory()->create(['process_request_id' => $requests->first()->id]);
        CaseNumber::factory()->create(['process_request_id' => $requests->last()->id]);
        CaseStarted::factory()->create(['case_number' => $caseNumber]);
        CaseParticipated::factory()->create(['case_number' => $caseNumber]);

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('process_requests', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('cases_started', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('cases_participated', ['case_number' => $caseNumber]);
        $this->assertDatabaseMissing('case_numbers', ['process_request_id' => $requests->first()->id]);
        $this->assertDatabaseMissing('case_numbers', ['process_request_id' => $requests->last()->id]);
    }

    public function testDeleteCaseRemovesDependentRecords(): void
    {
        $caseNumber = 24680;
        $request = ProcessRequest::factory()
            ->withCaseNumber($caseNumber)
            ->create();
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_id' => $request->process_id,
            'user_id' => $this->user->id,
        ]);

        CaseNumber::factory()->create(['process_request_id' => $request->id]);
        CaseStarted::factory()->create(['case_number' => $caseNumber]);
        CaseParticipated::factory()->create(['case_number' => $caseNumber]);

        ProcessRequestLock::factory()->create([
            'process_request_id' => $request->id,
            'process_request_token_id' => $token->id,
        ]);

        DB::table('scheduled_tasks')->insert(
            ScheduledTask::factory()->forToken($token)->raw([
                'type' => 'INTERMEDIATE_TIMER_EVENT',
                'configuration' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );

        $inboxRule = InboxRule::factory()->create([
            'user_id' => $this->user->id,
            'process_request_token_id' => $token->id,
        ]);

        InboxRuleLog::factory()->create([
            'user_id' => $this->user->id,
            'inbox_rule_id' => $inboxRule->id,
            'process_request_token_id' => $token->id,
        ]);

        ProcessAbeRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'process_request_token_id' => $token->id,
        ]);

        $draft = TaskDraft::factory()->create([
            'task_id' => $token->id,
            'data' => ['key1' => 'value1'],
        ]);

        Media::factory()->create([
            'model_type' => TaskDraft::class,
            'model_id' => $draft->id,
        ]);

        Media::factory()->create([
            'model_type' => ProcessRequest::class,
            'model_id' => $request->id,
            'custom_properties' => [
                'data_name' => 'case/file.txt',
            ],
        ]);

        Comment::factory()->create([
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $request->id,
            'case_number' => $caseNumber,
        ]);
        Comment::factory()->create([
            'commentable_type' => ProcessRequestToken::class,
            'commentable_id' => $token->id,
            'case_number' => $caseNumber,
        ]);

        if (Schema::hasTable('ellucian_ethos_sync_global_task_list')) {
            DB::table('ellucian_ethos_sync_global_task_list')->insert([
                'user_id' => $this->user->id,
                'global_task_uuid' => (string) Str::uuid(),
                'process_request_token_id' => $token->id,
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('process_request_tokens', ['id' => $token->id]);
        $this->assertDatabaseMissing('process_request_locks', ['process_request_id' => $request->id]);
        $this->assertDatabaseMissing('scheduled_tasks', ['process_request_id' => $request->id]);
        $this->assertDatabaseMissing('inbox_rules', ['id' => $inboxRule->id]);
        $this->assertDatabaseMissing('inbox_rule_logs', ['process_request_token_id' => $token->id]);
        $this->assertDatabaseMissing('process_abe_request_tokens', ['process_request_token_id' => $token->id]);
        $this->assertDatabaseMissing('task_drafts', ['task_id' => $token->id]);
        $this->assertDatabaseMissing('media', ['model_type' => TaskDraft::class, 'model_id' => $draft->id]);
        $this->assertDatabaseMissing('media', ['model_type' => ProcessRequest::class, 'model_id' => $request->id]);
        $this->assertSoftDeleted('comments', ['case_number' => $caseNumber]);
        if (Schema::hasTable('ellucian_ethos_sync_global_task_list')) {
            $this->assertDatabaseMissing('ellucian_ethos_sync_global_task_list', ['process_request_token_id' => $token->id]);
        }
    }

    public function testDeleteCaseRemovesCaseNotifications(): void
    {
        $caseNumber = 13579;
        $request = ProcessRequest::factory()
            ->withCaseNumber($caseNumber)
            ->create();
        $otherRequest = ProcessRequest::factory()
            ->withCaseNumber($caseNumber + 1)
            ->create();

        $notificationTypes = [
            'COMMENT',
            'FILE_SHARED',
            'TASK_CREATED',
            'TASK_COMPLETED',
            'TASK_REASSIGNED',
        ];

        $deletedNotificationIds = [];
        foreach ($notificationTypes as $type) {
            $deletedNotificationIds[] = Notification::factory()->create([
                'notifiable_type' => get_class($this->user),
                'notifiable_id' => $this->user->getKey(),
                'data' => json_encode([
                    'type' => $type,
                    'request_id' => $request->id,
                    'url' => "/requests/{$request->id}",
                ]),
                'url' => "/requests/{$request->id}",
            ])->id;
        }

        $keptDifferentRequest = Notification::factory()->create([
            'notifiable_type' => get_class($this->user),
            'notifiable_id' => $this->user->getKey(),
            'data' => json_encode([
                'type' => 'TASK_CREATED',
                'request_id' => $otherRequest->id,
                'url' => "/requests/{$otherRequest->id}",
            ]),
            'url' => "/requests/{$otherRequest->id}",
        ]);

        $keptDifferentType = Notification::factory()->create([
            'notifiable_type' => get_class($this->user),
            'notifiable_id' => $this->user->getKey(),
            'data' => json_encode([
                'type' => 'MESSAGE',
                'request_id' => $request->id,
                'url' => "/requests/{$request->id}",
            ]),
            'url' => "/requests/{$request->id}",
        ]);

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(204);
        foreach ($deletedNotificationIds as $notificationId) {
            $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
        }
        $this->assertDatabaseHas('notifications', ['id' => $keptDifferentRequest->id]);
        $this->assertDatabaseHas('notifications', ['id' => $keptDifferentType->id]);
    }

    public function testDeleteCaseReturnsNotFoundWhenMissing(): void
    {
        $caseNumber = 99999;

        $response = $this->apiCall('DELETE', route('api.cases.destroy', ['case_number' => $caseNumber]));

        $response->assertStatus(404);
    }
}
