<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class TaskActionByEmailResponseTest extends TestCase
{
    use RequestHelper;

    public static function resultCases(): array
    {
        $results = [
            'missing-link' => [404, 'Token not found', 'This link could not be found.'],
            'answered' => [200, 'This response has already been answered', 'This task has already been answered. No changes were made.'],
            'missing-task' => [404, 'Process request token not found', 'This task could not be found.'],
            'closed' => [404, 'Task already closed', 'This task is already closed.'],
            'submitted' => [200, 'Your response has been submitted.', 'Your response has been submitted.'],
            'exception' => [500, 'Error updating variable', 'An error occurred while processing your response.'],
        ];
        $cases = [];
        foreach ($results as $result => [$status, $jsonMessage, $htmlMessage]) {
            foreach ([false, true] as $json) {
                $cases[$result . ($json ? '-json' : '-html')] = [$result, $json, $status, $jsonMessage, $htmlMessage];
            }
        }

        return $cases;
    }

    #[DataProvider('resultCases')]
    public function testAbeResultFormat(string $result, bool $json, int $status, string $jsonMessage, string $htmlMessage): void
    {
        $abe = $this->createAbe();
        if ($result === 'answered') {
            $abe->update(['is_answered' => true]);
        } elseif ($result === 'missing-task') {
            $abe->update(['process_request_token_id' => null]);
        } elseif ($result === 'closed') {
            ProcessRequestToken::findOrFail($abe->process_request_token_id)->update(['status' => 'CLOSED']);
        }

        if ($result === 'submitted') {
            WorkflowManager::shouldReceive('completeTask')->once()->andReturnNull();
        } elseif ($result === 'exception') {
            WorkflowManager::shouldReceive('completeTask')->once()->andThrow(new \RuntimeException('Internal diagnostic detail'));
        } else {
            WorkflowManager::shouldReceive('completeTask')->never();
        }

        $uuid = $result === 'missing-link' ? (string) Str::uuid() : $abe->uuid;
        $url = '/tasks/update_variable/' . $uuid . '?varName=response&varValue=second';
        $this->actingAs($this->user, 'web');
        $response = $this->get($url, ['Accept' => $json ? 'application/json' : 'text/html']);
        $response->assertStatus($status);

        if ($json) {
            $expected = ['message' => $jsonMessage];
            if ($result === 'exception') {
                $expected['error'] = 'Internal diagnostic detail';
            }
            $response->assertExactJson($expected);
        } else {
            $response->assertViewIs('tasks.abe-response');
            $response->assertSee($htmlMessage);
            $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
            $response->assertDontSee('Internal diagnostic detail');
            $response->assertDontSee('javascript:history.back()', false);
            $response->assertDontSee('class="buttons"', false);
        }
    }

    public static function sessionCases(): array
    {
        return ['authenticated' => [true], 'guest' => [false]];
    }

    #[DataProvider('sessionCases')]
    public function testRepeatedAnswerDoesNotChangeOriginalState(bool $authenticated): void
    {
        $abe = $this->createAbe();
        $abe->update(['is_answered' => true, 'answered_at' => now()->subHour(), 'data' => json_encode(['response' => 'original']), 'user_id' => $this->user->id]);
        $task = ProcessRequestToken::findOrFail($abe->process_request_token_id);
        $task->update(['status' => 'CLOSED']);
        $case = ProcessRequest::findOrFail($abe->process_request_id);
        $before = [$abe->fresh()->getAttributes(), $task->fresh()->getAttributes(), $case->fresh()->getAttributes()];
        WorkflowManager::shouldReceive('completeTask')->never();

        if ($authenticated) {
            $this->actingAs($this->user, 'web');
        } else {
            Auth::forgetGuards();
        }
        $response = $this->get('/tasks/update_variable/' . $abe->uuid . '?varName=response&varValue=second');
        $response->assertOk()->assertViewIs('tasks.abe-response');
        $response->assertSee('This task has already been answered. No changes were made.');
        $this->assertSame($before, [$abe->fresh()->getAttributes(), $task->fresh()->getAttributes(), $case->fresh()->getAttributes()]);
    }

    public function testGuestMustStillLogInWhenRequired(): void
    {
        $abe = $this->createAbe();
        $abe->update(['is_answered' => true, 'require_login' => true]);
        Auth::forgetGuards();
        $response = $this->get('/tasks/update_variable/' . $abe->uuid . '?varName=response&varValue=second');
        $response->assertRedirect('login');
        $this->assertTrue((bool) $abe->fresh()->is_answered);
    }

    public function testStandardNotFoundPageRetainsNavigation(): void
    {
        $html = view('errors.404')->render();
        $this->assertStringContainsString('class="buttons"', $html);
        $this->assertStringContainsString('javascript:history.back()', $html);
    }

    private function createAbe(): ProcessAbeRequestToken
    {
        $process = Process::factory()->create(['user_id' => $this->user->id]);
        $case = ProcessRequest::factory()->create(['process_id' => $process->id, 'user_id' => $this->user->id]);
        $task = ProcessRequestToken::factory()->create([
            'process_id' => $process->id,
            'process_request_id' => $case->id,
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'is_self_service' => true,
        ]);

        return ProcessAbeRequestToken::create([
            'process_request_id' => $case->id,
            'process_request_token_id' => $task->id,
            'require_login' => false,
            'is_answered' => false,
        ]);
    }
}
