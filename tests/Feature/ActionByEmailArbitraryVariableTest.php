<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use ProcessMaker\Models\AnonymousUser;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Action-by-email submissions may apply only the variable/value pairs that
 * were on the email screen when the token was issued.
 *
 * The email screen is the screen id in the task's pm:configEmail screenEmailRef.
 * Each page in that screen's config is a list of items. An item contributes one
 * allowed pair when its config contains both varName and varValue. Those pairs
 * are fixed when the token is created. A later edit of the screen does not
 * change what an already issued token accepts.
 *
 * A submission whose varName or varValue is not one of those pairs must be
 * refused with HTTP 422. The refusal must not mark the token answered, must
 * not store the submitted pair on the token, must not set is_actionbyemail,
 * must not change process-request data, and must not close the task.
 *
 * The pair that was on the screen at issue time is still applied, including
 * when the screen was edited afterward. A second submission does not apply
 * another pair.
 *
 * These tests fail while the endpoint copies any query-string name and value
 * into the request and completes the task.
 */
class ActionByEmailArbitraryVariableTest extends TestCase
{
    use RequestHelper;

    public function testAnonymousGetCannotWriteArbitraryVariable(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'decision', 'varValue' => 'approve']],
            false
        );
        $this->seedRequestData($issued['request'], [
            'accountBalance' => '100',
            'decision' => 'pending',
        ]);

        $response = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'accountBalance',
            '0'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertSame('100', $issued['request']->data['accountBalance'] ?? null);
        $this->assertSame('pending', $issued['request']->data['decision'] ?? null);
        $this->assertSame('ACTIVE', $issued['task']->status);
        $this->assertFalse((bool) $issued['task']->is_actionbyemail);
        $this->assertFalse((bool) $issued['abe']->is_answered);
        $this->assertNull($issued['abe']->answered_at);
        $this->assertNull($issued['abe']->data);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testValueNotCapturedAtIssueTimeIsRejectedAfterTheScreenChanges(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'decision', 'varValue' => 'approve']],
            false
        );
        $this->replaceAllowedPairs($issued['screen'], [
            ['varName' => 'decision', 'varValue' => 'reject'],
            ['varName' => 'accountBalance', 'varValue' => '0'],
        ]);

        $response = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'decision',
            'reject'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertArrayNotHasKey('decision', $issued['request']->data ?? []);
        $this->assertArrayNotHasKey('accountBalance', $issued['request']->data ?? []);
        $this->assertSame('ACTIVE', $issued['task']->status);
        $this->assertFalse((bool) $issued['abe']->is_answered);
        $this->assertNull($issued['abe']->data);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testDifferentProcessAllowlistDoesNotAcceptAnotherScreensPair(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'status', 'varValue' => 'approved']],
            false
        );

        $response = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'decision',
            'approve'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertArrayNotHasKey('decision', $issued['request']->data ?? []);
        $this->assertArrayNotHasKey('status', $issued['request']->data ?? []);
        $this->assertSame('ACTIVE', $issued['task']->status);
        $this->assertFalse((bool) $issued['abe']->is_answered);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testAuthenticatedNonAssigneeCannotWriteArbitraryVariableWhenLoginIsRequired(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'decision', 'varValue' => 'approve']],
            true
        );
        $this->seedRequestData($issued['request'], [
            'accountBalance' => '100',
        ]);
        $this->assertNotSame($issued['task']->user_id, null);
        $stranger = User::factory()->create();
        $this->assertNotSame($issued['task']->user_id, $stranger->id);

        $response = $this->actingAs($stranger, 'web')->call('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'accountBalance',
            '0'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertSame('100', $issued['request']->data['accountBalance'] ?? null);
        $this->assertSame('ACTIVE', $issued['task']->status);
        $this->assertFalse((bool) $issued['task']->is_actionbyemail);
        $this->assertFalse((bool) $issued['abe']->is_answered);
        $this->assertNull($issued['abe']->data);
        $this->assertNotSame($stranger->id, $issued['abe']->user_id);
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testPairCapturedAtIssueTimeStillCompletesAfterTheScreenChanges(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'decision', 'varValue' => 'approve']],
            false
        );
        $this->replaceAllowedPairs($issued['screen'], [
            ['varName' => 'decision', 'varValue' => 'reject'],
        ]);

        $response = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'decision',
            'approve'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $response->assertStatus(200);
        $this->assertSame('approve', $issued['request']->data['decision'] ?? null);
        $this->assertSame('CLOSED', $issued['task']->status);
        $this->assertTrue((bool) $issued['task']->is_actionbyemail);
        $this->assertTrue((bool) $issued['abe']->is_answered);
    }

    public function testSecondSubmissionDoesNotApplyAnotherVariable(): void
    {
        $issued = $this->issueActionByEmailToken(
            [
                ['varName' => 'decision', 'varValue' => 'approve'],
                ['varName' => 'decision', 'varValue' => 'reject'],
            ],
            false
        );
        $this->seedRequestData($issued['request'], [
            'accountBalance' => '100',
        ]);

        $first = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'decision',
            'approve'
        ));
        $first->assertStatus(200);

        $second = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'accountBalance',
            '0'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertSame('approve', $issued['request']->data['decision'] ?? null);
        $this->assertSame('100', $issued['request']->data['accountBalance'] ?? null);
        $this->assertSame('CLOSED', $issued['task']->status);
        $this->assertTrue((bool) $issued['abe']->is_answered);
        $this->assertStringNotContainsString(
            'Your response has been submitted.',
            $second->getContent()
        );
    }

    public function testAnonymousUserIsRejectedWhenTheTokenRequiresLogin(): void
    {
        $issued = $this->issueActionByEmailToken(
            [['varName' => 'decision', 'varValue' => 'approve']],
            true
        );

        $response = $this->anonymousCall('GET', $this->submissionUrl(
            $issued['abe']->uuid,
            'accountBalance',
            '0'
        ));

        $issued['request']->refresh();
        $issued['task']->refresh();
        $issued['abe']->refresh();

        $this->assertArrayNotHasKey('accountBalance', $issued['request']->data ?? []);
        $this->assertSame('ACTIVE', $issued['task']->status);
        $this->assertFalse((bool) $issued['abe']->is_answered);
        $this->assertNull($issued['abe']->data);
        $this->assertTrue($response->isRedirect(route('login')));
    }

    /**
     * @param array<int, array{varName: string, varValue: string}> $allowedPairs
     * @return array{screen: Screen, process: Process, request: ProcessRequest, task: ProcessRequestToken, abe: ProcessAbeRequestToken}
     */
    private function issueActionByEmailToken(array $allowedPairs, bool $requireLogin): array
    {
        $screen = Screen::factory()->create([
            'type' => 'DISPLAY',
            'config' => $this->emailScreenConfig($allowedPairs),
        ]);

        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/action_by_email_process_no_require_login.bpmn');
        $bpmn = preg_replace(
            '/screenEmailRef&#34;:\d+/',
            'screenEmailRef&#34;:' . $screen->id,
            $bpmn,
            1
        );
        $bpmn = str_replace(
            'requireLogin&#34;:false',
            'requireLogin&#34;:' . ($requireLogin ? 'true' : 'false'),
            $bpmn
        );

        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);

        $route = route('api.process_events.trigger', [$process->id, 'event' => 'node_1']);
        $started = $this->apiCall('POST', $route, []);
        $started->assertStatus(201);

        $task = ProcessRequestToken::where('process_id', $process->id)
            ->where('status', 'ACTIVE')
            ->firstOrFail();
        $abe = ProcessAbeRequestToken::where('process_request_token_id', $task->id)->firstOrFail();
        $this->assertSame($requireLogin ? 1 : 0, (int) $abe->require_login);
        $this->assertSame($screen->id, $this->emailScreenIdFromProcess($process));

        return [
            'screen' => $screen,
            'process' => $process,
            'request' => $task->processRequest,
            'task' => $task,
            'abe' => $abe,
        ];
    }

    /**
     * @param array<int, array{varName: string, varValue: string}> $allowedPairs
     */
    private function replaceAllowedPairs(Screen $screen, array $allowedPairs): void
    {
        $screen->config = $this->emailScreenConfig($allowedPairs);
        $screen->save();
    }

    /**
     * @param array<string, string> $data
     */
    private function seedRequestData(ProcessRequest $request, array $data): void
    {
        $request->data = $data;
        $request->save();
    }

    private function submissionUrl(string $uuid, string $varName, string $varValue): string
    {
        return '/tasks/update_variable/' . $uuid . '?' . http_build_query([
            'varName' => $varName,
            'varValue' => $varValue,
        ]);
    }

    private function anonymousCall(string $method, string $url)
    {
        $this->flushSession();
        Auth::forgetGuards();
        Auth::shouldUse('web');

        $response = $this->call($method, $url, []);

        $anonymous = User::where('username', AnonymousUser::ANONYMOUS_USERNAME)->firstOrFail();
        $this->assertNotSame(
            $anonymous->id,
            $this->user->id,
            'The fixture user must not be the anonymous user.'
        );

        return $response;
    }

    /**
     * @param array<int, array{varName: string, varValue: string}> $allowedPairs
     */
    private function emailScreenConfig(array $allowedPairs): array
    {
        return [
            [
                'items' => array_map(function (array $pair) {
                    return [
                        'component' => 'ActionByEmailButton',
                        'config' => [
                            'label' => $pair['varValue'],
                            'varName' => $pair['varName'],
                            'varValue' => $pair['varValue'],
                            'href' => '{{abe_uri}}/tasks/update_variable/{{token_abe}}?varName='
                                . rawurlencode($pair['varName'])
                                . '&varValue='
                                . rawurlencode($pair['varValue']),
                        ],
                    ];
                }, $allowedPairs),
            ],
        ];
    }

    private function emailScreenIdFromProcess(Process $process): int
    {
        $configEmail = null;
        if (preg_match('/pm:configEmail="([^"]+)"/', $process->bpmn, $matches) === 1) {
            $configEmail = json_decode(html_entity_decode($matches[1]), true);
        }

        return (int) ($configEmail['screenEmailRef'] ?? 0);
    }
}
