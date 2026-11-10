<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\InboxRuleLog;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

/**
 * Inbox rule show, update, delete, and update-active must refuse a rule
 * that belongs to someone else, and must not change that rule or the
 * tasks it would apply to.
 */
class InboxRulesCrossUserAccessTest extends TestCase
{
    public function testShowRejectsAnotherUsersRule(): void
    {
        $victim = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->victimRule($victim, [
            'data' => ['account_number' => 'victim-only-value'],
        ]);

        $response = $this->actingAs($attacker, 'api')
            ->getJson('/api/1.0/tasks/rules/' . $rule->id);

        $response->assertJsonMissing(['account_number' => 'victim-only-value']);
        $response->assertForbidden();

        $own = $this->actingAs($victim, 'api')
            ->getJson('/api/1.0/tasks/rules/' . $rule->id);
        $own->assertOk();
        $own->assertJsonFragment(['account_number' => 'victim-only-value']);
    }

    public function testUpdateDoesNotRewriteAnotherUsersRuleOrApplyIt(): void
    {
        $victim = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $task = $this->activeTask($victim);
        $rule = $this->victimRule($victim, [
            'process_request_token_id' => $task->id,
            'active' => false,
            'submit_data' => false,
            'mark_as_priority' => false,
            'make_draft' => false,
            'reassign_to_user_id' => null,
            'data' => ['account_number' => 'victim-only-value'],
            'name' => 'Victim Rule',
        ]);
        $decoy = $this->victimRule($this->ordinaryUser(), [
            'name' => 'Decoy Rule',
            'data' => ['account_number' => 'decoy-value'],
            'active' => false,
        ]);

        $response = $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id, [
                'id' => $decoy->id,
                'user_id' => $attacker->id,
                'inbox_rule' => $decoy->id,
                'name' => 'Taken Over',
                'active' => true,
                'submit_data' => true,
                'make_draft' => true,
                'mark_as_priority' => true,
                'reassign_to_user_id' => $attacker->id,
                'data' => ['account_number' => 'attacker-supplied'],
                'apply_to_current_tasks' => true,
                'columns' => ['id'],
                'advanced_filter' => null,
                'submit_button' => null,
                'end_date' => null,
            ]);

        $rule->refresh();
        $decoy->refresh();
        $task->refresh();
        $observed = json_encode([
            'http_status' => $response->status(),
            'rule' => $rule->only([
                'name', 'user_id', 'active', 'submit_data', 'make_draft',
                'mark_as_priority', 'reassign_to_user_id', 'data',
            ]),
            'decoy_name' => $decoy->name,
            'task_status' => $task->status,
            'task_user_id' => $task->user_id,
            'task_is_priority' => (bool) $task->is_priority,
            'task_request_data' => $task->processRequest->fresh()->data,
            'inbox_rule_logs' => InboxRuleLog::query()->where('process_request_token_id', $task->id)->count(),
        ]);

        $this->assertSame('Victim Rule', $rule->name, $observed);
        $response->assertForbidden();
        $this->assertSame($victim->id, $rule->user_id, $observed);
        $this->assertFalse($rule->active, $observed);
        $this->assertFalse($rule->submit_data, $observed);
        $this->assertFalse($rule->make_draft, $observed);
        $this->assertFalse($rule->mark_as_priority, $observed);
        $this->assertNull($rule->reassign_to_user_id, $observed);
        $this->assertSame(['account_number' => 'victim-only-value'], $rule->data, $observed);
        $this->assertSame('Decoy Rule', $decoy->name, $observed);
        $this->assertSame(['account_number' => 'decoy-value'], $decoy->data, $observed);
        $this->assertSame('ACTIVE', $task->status, $observed);
        $this->assertSame($victim->id, $task->user_id, $observed);
        $this->assertFalse((bool) $task->is_priority, $observed);
        $this->assertSame(0, InboxRuleLog::query()->where('process_request_token_id', $task->id)->count(), $observed);
        $this->assertArrayNotHasKey('account_number', $task->processRequest->fresh()->data ?? [], $observed);

        $own = $this->victimRule($attacker, [
            'name' => 'Attacker Rule',
            'active' => false,
        ]);
        $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $own->id, [
                'name' => 'Attacker Rule Renamed',
                'active' => true,
                'submit_data' => false,
                'make_draft' => false,
                'mark_as_priority' => false,
                'reassign_to_user_id' => null,
                'data' => ['account_number' => 'own-value'],
                'apply_to_current_tasks' => false,
            ]);
        $own->refresh();
        $this->assertSame('Attacker Rule Renamed', $own->name);
        $this->assertTrue($own->active);
    }

    public function testDestroyDoesNotDeleteAnotherUsersRule(): void
    {
        $victim = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->victimRule($victim, ['name' => 'Victim Rule']);
        $own = $this->victimRule($attacker, ['name' => 'Attacker Rule']);

        $response = $this->actingAs($attacker, 'api')
            ->deleteJson('/api/1.0/tasks/rules/' . $rule->id);

        $this->assertNotNull(InboxRule::query()->find($rule->id));
        $response->assertForbidden();

        $this->actingAs($attacker, 'api')
            ->deleteJson('/api/1.0/tasks/rules/' . $own->id)
            ->assertNoContent();
        $this->assertNull(InboxRule::query()->find($own->id));
        $this->assertNotNull(InboxRule::query()->find($rule->id));
    }

    public function testUpdateActiveDoesNotChangeAnotherUsersRule(): void
    {
        $victim = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->victimRule($victim, ['active' => true, 'name' => 'Victim Rule']);
        $decoy = $this->victimRule($this->ordinaryUser(), ['active' => true, 'name' => 'Decoy Rule']);

        $response = $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $rule->id . '/update-active', [
                'active' => false,
                'id' => $decoy->id,
                'inbox_rule' => $decoy->id,
                'user_id' => $attacker->id,
            ]);

        $this->assertTrue((bool) $rule->fresh()->active);
        $response->assertForbidden();
        $this->assertSame('Victim Rule', $rule->fresh()->name);
        $this->assertTrue((bool) $decoy->fresh()->active);

        $own = $this->victimRule($attacker, ['active' => true]);
        $this->actingAs($attacker, 'api')
            ->putJson('/api/1.0/tasks/rules/' . $own->id . '/update-active', [
                'active' => false,
            ])
            ->assertNoContent();
        $this->assertFalse((bool) $own->fresh()->active);
    }

    public function testIndexAndExecutionLogStayLimitedToTheCaller(): void
    {
        $victim = $this->ordinaryUser();
        $attacker = $this->ordinaryUser();
        $rule = $this->victimRule($victim, ['name' => 'Victim Rule']);
        $task = $this->activeTask($victim);
        InboxRuleLog::factory()->create([
            'user_id' => $victim->id,
            'inbox_rule_id' => $rule->id,
            'process_request_token_id' => $task->id,
        ]);

        $index = $this->actingAs($attacker, 'api')->getJson('/api/1.0/tasks/rules');
        $index->assertOk();
        $ids = collect($index->json('data'))->pluck('id')->all();
        $this->assertNotContains($rule->id, $ids);

        $log = $this->actingAs($attacker, 'api')->getJson('/api/1.0/tasks/rule-execution-log');
        $log->assertOk();
        $loggedRuleIds = collect($log->json('data'))->pluck('inbox_rule_id')->all();
        $this->assertNotContains($rule->id, $loggedRuleIds);
    }

    private function ordinaryUser(): User
    {
        return User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);
    }

    private function activeTask(User $owner): ProcessRequestToken
    {
        return ProcessRequestToken::factory()->create([
            'user_id' => $owner->id,
            'status' => 'ACTIVE',
            'element_id' => 'node_2',
            'element_type' => 'task',
            'is_priority' => false,
        ]);
    }

    private function victimRule(User $owner, array $overrides = []): InboxRule
    {
        return InboxRule::factory()->create(array_merge([
            'user_id' => $owner->id,
            'saved_search_id' => null,
            'process_request_token_id' => null,
            'active' => true,
            'submit_data' => false,
            'make_draft' => false,
            'mark_as_priority' => false,
            'reassign_to_user_id' => null,
            'data' => ['account_number' => 'victim-only-value'],
            'name' => 'Victim Rule ' . $owner->id,
        ], $overrides));
    }
}
