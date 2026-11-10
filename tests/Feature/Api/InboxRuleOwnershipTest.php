<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\TaskDraft;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Show, update, delete, and update-active must refuse a rule whose user_id is
 * not the caller, and must not write that rule or apply it to tasks.
 *
 * These assertions fail while those actions load the rule by id and use it
 * without comparing user_id.
 */
class InboxRuleOwnershipTest extends TestCase
{
    public function test_another_user_cannot_read_an_inbox_rule(): void
    {
        $owner = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->ruleFor($owner, [
            'name' => 'Owner Only Rule',
            'data' => ['account' => 'owner-value'],
        ]);

        $response = $this->actingAs($attacker, 'api')
            ->getJson('/api/1.0/tasks/rules/' . $rule->id);

        $this->assertStringNotContainsString('owner-value', $response->getContent());
        $this->assertStringNotContainsString('Owner Only Rule', $response->getContent());
        $response->assertForbidden();
    }

    public function test_owner_can_read_their_own_inbox_rule(): void
    {
        $owner = $this->ordinaryUser();
        $rule = $this->ruleFor($owner, ['name' => 'My Rule']);

        $response = $this->actingAs($owner, 'api')
            ->getJson('/api/1.0/tasks/rules/' . $rule->id);

        $response->assertOk();
        $response->assertJsonPath('name', 'My Rule');
    }

    public function test_index_returns_only_the_callers_rules(): void
    {
        $owner = $this->ordinaryUser();
        $other = $this->ordinaryUser();
        $ownRule = $this->ruleFor($owner, ['name' => 'Mine']);
        $otherRule = $this->ruleFor($other, ['name' => 'Theirs']);

        $response = $this->actingAs($owner, 'api')
            ->getJson('/api/1.0/tasks/rules');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($ownRule->id, $ids);
        $this->assertNotContains($otherRule->id, $ids);
    }

    public function test_another_user_cannot_update_an_inbox_rule_or_apply_it_to_tasks(): void
    {
        [$owner, $attacker, $rule, $task] = $this->ruleBoundToActiveTask();
        $attackerRule = $this->ruleFor($attacker, ['name' => 'Attacker Rule']);
        $original = $this->snapshot($rule);
        $originalRequestData = $task->processRequest->data;
        $originalTaskUserId = $task->user_id;

        $response = $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id, $this->weaponizedPayload($attacker, [
                'id' => $attackerRule->id,
                'inbox_rule_id' => $attackerRule->id,
            ]));

        $rule->refresh();
        $task->refresh();
        $task->processRequest->refresh();
        $this->assertSame([
            'response_status' => 403,
            'rule' => $original,
            'task_status' => 'ACTIVE',
            'task_user_id' => $originalTaskUserId,
            'task_is_priority' => false,
            'request_data' => $originalRequestData,
            'inbox_rule_logs' => 0,
            'task_draft' => null,
        ], [
            'response_status' => $response->status(),
            'rule' => $this->snapshot($rule),
            'task_status' => $task->status,
            'task_user_id' => $task->user_id,
            'task_is_priority' => (bool) $task->is_priority,
            'request_data' => $task->processRequest->data,
            'inbox_rule_logs' => InboxRuleLog::query()->where('process_request_token_id', $task->id)->count(),
            'task_draft' => TaskDraft::query()->where('task_id', $task->id)->first(),
        ]);
    }

    public function test_updating_an_owned_rule_does_not_change_a_rule_named_in_the_body(): void
    {
        [$owner, $attacker, $victimRule, $victimTask] = $this->ruleBoundToActiveTask();
        $attackerRule = $this->ruleFor($attacker, [
            'name' => 'Attacker Rule',
            'active' => false,
            'submit_data' => false,
            'data' => ['own' => 'kept'],
            'reassign_to_user_id' => null,
            'mark_as_priority' => false,
        ]);
        $victimSnapshot = $this->snapshot($victimRule);
        $originalRequestData = $victimTask->processRequest->data;
        $originalTaskUserId = $victimTask->user_id;

        $response = $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $attackerRule->id, $this->weaponizedPayload($attacker, [
                'id' => $victimRule->id,
                'inbox_rule_id' => $victimRule->id,
                'user_id' => $owner->id,
            ]));

        $this->assertNotSame(403, $response->status());
        $attackerRule->refresh();
        $this->assertSame($attacker->id, $attackerRule->user_id);
        $this->assertSame('Hijacked Rule', $attackerRule->name);
        $this->assertRuleUnchanged($victimRule, $victimSnapshot);
        $this->assertTaskUntouched($victimTask, $originalTaskUserId, $originalRequestData);
        $this->assertSame(0, InboxRuleLog::query()->where('process_request_token_id', $victimTask->id)->count());
    }

    public function test_owner_update_is_not_forbidden(): void
    {
        $owner = $this->ordinaryUser();
        $rule = $this->ruleFor($owner, [
            'name' => 'Original',
            'active' => false,
            'data' => ['kept' => 'yes'],
        ]);

        $response = $this->actingAs($owner, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id, [
                'name' => 'Renamed By Owner',
                'active' => true,
                'data' => ['kept' => 'yes'],
            ]);

        $this->assertNotSame(403, $response->status());
        $rule->refresh();
        $this->assertSame($owner->id, $rule->user_id);
        $this->assertSame('Renamed By Owner', $rule->name);
        $this->assertTrue($rule->active);
    }

    public function test_another_user_cannot_delete_an_inbox_rule(): void
    {
        $owner = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->ruleFor($owner);

        $response = $this->actingAs($attacker, 'api')
            ->deleteJson('/api/1.0/tasks/rules/' . $rule->id);

        $this->assertNotNull(InboxRule::query()->find($rule->id));
        $response->assertForbidden();
    }

    public function test_owner_can_delete_their_own_inbox_rule(): void
    {
        $owner = $this->ordinaryUser();
        $rule = $this->ruleFor($owner);

        $response = $this->actingAs($owner, 'api')
            ->deleteJson('/api/1.0/tasks/rules/' . $rule->id);

        $response->assertNoContent();
        $this->assertNull(InboxRule::query()->find($rule->id));
    }

    public function test_another_user_cannot_change_the_active_flag(): void
    {
        $owner = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->ruleFor($owner, ['active' => true]);

        $response = $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id . '/update-active', [
                'active' => false,
                'id' => $rule->id,
            ]);

        $rule->refresh();
        $this->assertTrue($rule->active);
        $response->assertForbidden();
    }

    public function test_owner_can_change_their_own_active_flag(): void
    {
        $owner = $this->ordinaryUser();
        $rule = $this->ruleFor($owner, ['active' => true]);

        $response = $this->actingAs($owner, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id . '/update-active', [
                'active' => false,
            ]);

        $response->assertNoContent();
        $rule->refresh();
        $this->assertFalse($rule->active);
    }

    private function ordinaryUser(): User
    {
        return User::factory()->create([
            'status' => 'ACTIVE',
            'is_administrator' => false,
        ]);
    }

    private function ruleFor(User $owner, array $overrides = []): InboxRule
    {
        return InboxRule::factory()->create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Inbox Rule',
            'active' => false,
            'saved_search_id' => null,
            'process_request_token_id' => null,
            'mark_as_priority' => false,
            'reassign_to_user_id' => null,
            'make_draft' => false,
            'submit_data' => false,
            'submit_button' => null,
            'data' => ['field' => 'original'],
            'end_date' => null,
        ], $overrides));
    }

    /**
     * @return array{0: User, 1: User, 2: InboxRule, 3: ProcessRequestToken}
     */
    private function ruleBoundToActiveTask(): array
    {
        $owner = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $task = ProcessRequestToken::factory()->create([
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
            'element_id' => 'UserTaskUID',
            'element_type' => 'task',
            'is_priority' => false,
        ]);
        $rule = $this->ruleFor($owner, [
            'process_request_token_id' => $task->id,
            'active' => false,
            'submit_data' => false,
            'mark_as_priority' => false,
            'make_draft' => false,
            'data' => ['field' => 'original'],
        ]);

        return [$owner, $attacker, $rule, $task];
    }

    private function weaponizedPayload(User $attacker, array $extra = []): array
    {
        return array_merge([
            'name' => 'Hijacked Rule',
            'active' => true,
            'submit_data' => true,
            'make_draft' => true,
            'mark_as_priority' => true,
            'reassign_to_user_id' => $attacker->id,
            'data' => ['approved' => true, 'field' => 'attacker'],
            'apply_to_current_tasks' => true,
            'user_id' => $attacker->id,
        ], $extra);
    }

    private function snapshot(InboxRule $rule): array
    {
        $rule->refresh();

        return [
            'name' => $rule->name,
            'user_id' => $rule->user_id,
            'active' => $rule->active,
            'submit_data' => $rule->submit_data,
            'make_draft' => $rule->make_draft,
            'mark_as_priority' => $rule->mark_as_priority,
            'reassign_to_user_id' => $rule->reassign_to_user_id,
            'data' => $rule->data,
            'process_request_token_id' => $rule->process_request_token_id,
            'saved_search_id' => $rule->saved_search_id,
        ];
    }

    private function assertRuleUnchanged(InboxRule $rule, array $original): void
    {
        $rule->refresh();
        $this->assertSame($original['name'], $rule->name);
        $this->assertSame($original['user_id'], $rule->user_id);
        $this->assertSame($original['active'], $rule->active);
        $this->assertSame($original['submit_data'], $rule->submit_data);
        $this->assertSame($original['make_draft'], $rule->make_draft);
        $this->assertSame($original['mark_as_priority'], $rule->mark_as_priority);
        $this->assertSame($original['reassign_to_user_id'], $rule->reassign_to_user_id);
        $this->assertSame($original['data'], $rule->data);
        $this->assertSame($original['process_request_token_id'], $rule->process_request_token_id);
        $this->assertSame($original['saved_search_id'], $rule->saved_search_id);
    }

    private function assertTaskUntouched(ProcessRequestToken $task, int $userId, mixed $requestData): void
    {
        $task->refresh();
        $this->assertSame('ACTIVE', $task->status);
        $this->assertSame($userId, $task->user_id);
        $this->assertFalse((bool) $task->is_priority);
        $task->processRequest->refresh();
        $this->assertSame($requestData, $task->processRequest->data);
    }
}
