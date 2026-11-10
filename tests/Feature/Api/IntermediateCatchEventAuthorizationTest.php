<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\GroupMember;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Spec for POST /api/1.0/requests/{request}/events/{event}.
 *
 * An intermediate catch event with no IP whitelist and no allowed users or
 * groups must not be completed by an authenticated caller, and the JSON body
 * must not be merged into the request. These tests fail while that call is
 * still accepted.
 */
class IntermediateCatchEventAuthorizationTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const ORIGINAL_TOTAL = 'original-total';

    private const REPLACED_TOTAL = 'replaced-total';

    private const INJECTED = 'owned';

    public function testStrangerCannotCompleteMessageCatchOrWriteRequestData(): void
    {
        [$owner, $stranger] = $this->users();
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>'),
            'catch_message'
        );
    }

    public function testRequestOwnerCannotCompleteMessageCatchWhenNoAllowListIsConfigured(): void
    {
        [$owner] = $this->users();
        $this->assertCatchStaysPut(
            $owner,
            $owner,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>'),
            'catch_message'
        );
    }

    public function testUserWithEditRequestDataCannotCompleteMessageCatchWhenNoAllowListIsConfigured(): void
    {
        [$owner, $editor] = $this->users();
        $editor->giveDirectPermission('edit-request_data');
        $editor->refresh();

        $this->assertCatchStaysPut(
            $owner,
            $editor,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>'),
            'catch_message'
        );
    }

    public function testEmptyAllowListAttributesAreNotPublic(): void
    {
        [$owner, $stranger] = $this->users();
        $catchAttributes = 'pm:whitelist="" pm:allowedUsers="" pm:allowedGroups=""';
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>', $catchAttributes),
            'catch_message'
        );
    }

    public function testUndefinedAllowListAttributesAreNotPublic(): void
    {
        [$owner, $stranger] = $this->users();
        $catchAttributes = 'pm:whitelist="undefined" pm:allowedUsers="undefined" pm:allowedGroups="undefined"';
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>', $catchAttributes),
            'catch_message'
        );
    }

    public function testNamedMessageVariableIsNotWrittenWhenNoAllowListIsConfigured(): void
    {
        [$owner, $stranger] = $this->users();
        $definition = '<bpmn:messageEventDefinition id="message_def" messageRef="message_1" pm:variableName="payload"/>';
        [$request, $token] = $this->startCatch(
            $owner,
            $this->catchBpmn('catch_message', $definition, '<bpmn:message id="message_1" name="message_1"/>'),
            'catch_message'
        );

        $response = $this->postCatch($stranger, $request, 'catch_message');

        $request->refresh();
        $token->refresh();
        $this->assertSame(
            403,
            $response->status(),
            'HTTP ' . $response->status()
            . ' request=' . $request->status
            . ' token=' . $token->status
            . ' data=' . json_encode($request->data)
        );
        $this->assertSame(self::ORIGINAL_TOTAL, $request->data['invoice_total']);
        $this->assertArrayNotHasKey('payload', $request->data);
        $this->assertArrayNotHasKey('injected', $request->data);
        $this->assertSame('ACTIVE', $request->status);
        $this->assertSame('ACTIVE', $token->status);
    }

    public function testStrangerCannotCompleteSignalCatchWhenNoAllowListIsConfigured(): void
    {
        [$owner, $stranger] = $this->users();
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_signal', '<bpmn:signalEventDefinition id="signal_def" signalRef="signal_1"/>', '<bpmn:signal id="signal_1" name="signal_1"/>'),
            'catch_signal'
        );
    }

    public function testStrangerCannotCompleteTimerCatchWhenNoAllowListIsConfigured(): void
    {
        [$owner, $stranger] = $this->users();
        $definition = '<bpmn:timerEventDefinition id="timer_def"><bpmn:timeDuration>PT4H</bpmn:timeDuration></bpmn:timerEventDefinition>';
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_timer', $definition),
            'catch_timer'
        );
    }

    public function testStrangerCannotCompleteConditionalCatchWhenNoAllowListIsConfigured(): void
    {
        [$owner, $stranger] = $this->users();
        $definition = '<bpmn:conditionalEventDefinition id="cond_def"><bpmn:condition xsi:type="bpmn:tFormalExpression">false</bpmn:condition></bpmn:conditionalEventDefinition>';
        $this->assertCatchStaysPut(
            $owner,
            $stranger,
            $this->catchBpmn('catch_conditional', $definition),
            'catch_conditional'
        );
    }

    public function testGuestCannotCompleteMessageCatch(): void
    {
        [$owner] = $this->users();
        [$request, $token] = $this->startCatch(
            $owner,
            $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>'),
            'catch_message'
        );
        Auth::logout();

        $response = $this->postJson($this->eventUrl($request, 'catch_message'), $this->payload());

        $response->assertUnauthorized();
        $this->assertUnchanged($request, $token);
    }

    public function testUserListedInAllowedUsersCanCompleteMessageCatchAndWriteData(): void
    {
        [$owner, $allowed] = $this->users();
        $bpmn = $this->catchBpmn(
            'catch_message',
            '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>',
            '<bpmn:message id="message_1" name="message_1"/>',
            'pm:allowedUsers="' . $allowed->id . '"'
        );
        [$request, $token] = $this->startCatch($owner, $bpmn, 'catch_message');

        $response = $this->postCatch($allowed, $request, 'catch_message');

        $response->assertSuccessful();
        $request->refresh();
        $token->refresh();
        $this->assertSame(self::REPLACED_TOTAL, $request->data['invoice_total']);
        $this->assertSame(self::INJECTED, $request->data['injected']);
        $this->assertNotSame('ACTIVE', $token->status);
    }

    public function testUserAbsentFromAllowedUsersCannotCompleteMessageCatch(): void
    {
        [$owner, $stranger, $listed] = $this->users();
        $bpmn = $this->catchBpmn(
            'catch_message',
            '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>',
            '<bpmn:message id="message_1" name="message_1"/>',
            'pm:allowedUsers="' . $listed->id . '"'
        );
        $this->assertCatchStaysPut($owner, $stranger, $bpmn, 'catch_message');
    }

    public function testAllowedGroupMemberCanCompleteMessageCatch(): void
    {
        [$owner, $member] = $this->users();
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        GroupMember::factory()->create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'member_type' => User::class,
        ]);
        $bpmn = $this->catchBpmn(
            'catch_message',
            '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>',
            '<bpmn:message id="message_1" name="message_1"/>',
            'pm:allowedGroups="' . $group->id . '"'
        );
        [$request, $token] = $this->startCatch($owner, $bpmn, 'catch_message');

        $response = $this->postCatch($member, $request, 'catch_message');

        $response->assertSuccessful();
        $request->refresh();
        $token->refresh();
        $this->assertSame(self::REPLACED_TOTAL, $request->data['invoice_total']);
        $this->assertSame(self::INJECTED, $request->data['injected']);
        $this->assertNotSame('ACTIVE', $token->status);
    }

    public function testUserOutsideAllowedGroupCannotCompleteMessageCatch(): void
    {
        [$owner, $stranger] = $this->users();
        $group = Group::factory()->create(['status' => 'ACTIVE']);
        $bpmn = $this->catchBpmn(
            'catch_message',
            '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>',
            '<bpmn:message id="message_1" name="message_1"/>',
            'pm:allowedGroups="' . $group->id . '"'
        );
        $this->assertCatchStaysPut($owner, $stranger, $bpmn, 'catch_message');
    }

    public function testNonMatchingWhitelistCannotCompleteMessageCatch(): void
    {
        [$owner, $stranger] = $this->users();
        $bpmn = $this->catchBpmn(
            'catch_message',
            '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>',
            '<bpmn:message id="message_1" name="message_1"/>',
            'pm:whitelist="10.9.9.9" pm:allowedUsers="' . $stranger->id . '"'
        );
        $this->assertCatchStaysPut($owner, $stranger, $bpmn, 'catch_message');
    }

    public function testCallerCannotMutateADifferentRequestThanTheOneAddressed(): void
    {
        [$owner, $stranger] = $this->users();
        $bpmn = $this->catchBpmn('catch_message', '<bpmn:messageEventDefinition id="message_def" messageRef="message_1"/>', '<bpmn:message id="message_1" name="message_1"/>');
        [$target, $targetToken] = $this->startCatch($owner, $bpmn, 'catch_message');
        [$other, $otherToken] = $this->startCatch($owner, $bpmn, 'catch_message');

        $response = $this->postCatch($stranger, $target, 'catch_message');

        $response->assertForbidden();
        $this->assertUnchanged($target, $targetToken);
        $this->assertUnchanged($other, $otherToken);
    }

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function users(): array
    {
        return [
            User::factory()->create(['status' => 'ACTIVE', 'is_administrator' => false]),
            User::factory()->create(['status' => 'ACTIVE', 'is_administrator' => false]),
            User::factory()->create(['status' => 'ACTIVE', 'is_administrator' => false]),
        ];
    }

    private function assertCatchStaysPut(User $owner, User $caller, string $bpmn, string $elementId): void
    {
        [$request, $token] = $this->startCatch($owner, $bpmn, $elementId);

        $response = $this->postCatch($caller, $request, $elementId);

        $freshRequest = $request->fresh();
        $freshToken = $token->fresh();
        $this->assertSame(
            403,
            $response->status(),
            'HTTP ' . $response->status()
            . ' request=' . $freshRequest->status
            . ' token=' . $freshToken->status
            . ' completed_at=' . ($freshToken->completed_at ? 'set' : 'null')
            . ' data=' . json_encode($freshRequest->data)
        );
        $this->assertUnchanged($request, $token);
    }

    /**
     * @return array{0: ProcessRequest, 1: ProcessRequestToken}
     */
    private function startCatch(User $owner, string $bpmn, string $elementId): array
    {
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
            'user_id' => $owner->id,
        ]);
        Auth::login($owner);
        $request = WorkflowManager::triggerStartEvent(
            $process,
            $process->getDefinitions()->getEvent('start'),
            ['invoice_total' => self::ORIGINAL_TOTAL]
        );
        Auth::logout();

        $request = ProcessRequest::findOrFail($request->getKey());
        $token = $request->tokens()->where('element_id', $elementId)->where('status', 'ACTIVE')->first();
        $this->assertNotNull($token, 'The catch event did not leave an active token.');
        $this->assertSame($owner->id, $request->user_id);
        $this->assertSame(self::ORIGINAL_TOTAL, $request->data['invoice_total']);

        return [$request, $token];
    }

    private function postCatch(User $caller, ProcessRequest $request, string $elementId)
    {
        return $this->actingAs($caller, 'api')->postJson(
            $this->eventUrl($request, $elementId),
            $this->payload()
        );
    }

    private function eventUrl(ProcessRequest $request, string $elementId): string
    {
        return '/api/1.0/requests/' . $request->id . '/events/' . $elementId;
    }

    private function payload(): array
    {
        return [
            'invoice_total' => self::REPLACED_TOTAL,
            'injected' => self::INJECTED,
        ];
    }

    private function assertUnchanged(ProcessRequest $request, ProcessRequestToken $token): void
    {
        $request->refresh();
        $token->refresh();
        $this->assertSame('ACTIVE', $request->status);
        $this->assertSame(self::ORIGINAL_TOTAL, $request->data['invoice_total']);
        $this->assertArrayNotHasKey('injected', $request->data);
        $this->assertArrayNotHasKey('payload', $request->data);
        $this->assertSame('ACTIVE', $token->status);
        $this->assertNull($token->completed_at);
    }

    private function catchBpmn(string $elementId, string $eventDefinition, string $rootElements = '', string $catchAttributes = ''): string
    {
        $attributes = $catchAttributes === '' ? '' : ' ' . $catchAttributes;

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd" id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn:process id="Process_1" name="Catch" isExecutable="true">
    <bpmn:startEvent id="start">
      <bpmn:outgoing>flow_start</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:sequenceFlow id="flow_start" sourceRef="start" targetRef="{$elementId}"/>
    <bpmn:intermediateCatchEvent id="{$elementId}" name="Wait"{$attributes}>
      <bpmn:incoming>flow_start</bpmn:incoming>
      <bpmn:outgoing>flow_end</bpmn:outgoing>
      {$eventDefinition}
    </bpmn:intermediateCatchEvent>
    <bpmn:sequenceFlow id="flow_end" sourceRef="{$elementId}" targetRef="end"/>
    <bpmn:endEvent id="end">
      <bpmn:incoming>flow_end</bpmn:incoming>
    </bpmn:endEvent>
  </bpmn:process>
  {$rootElements}
</bpmn:definitions>
XML;
    }
}
