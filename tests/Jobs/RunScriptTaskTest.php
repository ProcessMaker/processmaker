<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Mockery;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\ErrorHandling;
use ProcessMaker\Jobs\RunNayraScriptTask;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;

class RunScriptTaskTest extends TestCase
{
    /**
     * @dataProvider jobTypes
     */
    public function testScriptNotSet($class)
    {
        $request = $this->runJob($class, '');
        $tokens = $request->tokens;

        $this->assertEmpty($request->errors);
        $this->assertEquals('Script Task: No code or script assigned to "Script Task"', $request->data['ScriptConfigurationError']);
    }

    /**
     * @dataProvider jobTypes
     */
    public function testScriptNotFound($class)
    {
        $request = $this->runJob($class, 12345);
        $tokens = $request->tokens;

        $this->assertEmpty($request->errors);
        $this->assertEquals('Script Task: Script "12345" not found', $request->data['ScriptConfigurationError']);
    }

    /**
     * @dataProvider jobTypes
     */
    public function testRunAsUserNotFound($class)
    {
        $script = Script::factory()->create(['run_as_user_id' => null]);
        $request = $this->runJob($class, $script->id);
        $tokens = $request->tokens;

        $this->assertEmpty($request->errors);
        $this->assertEquals('Script Task: A user is required to run scripts', $request->data['ScriptConfigurationError']);
    }

    private function runJob($class, $scriptId)
    {
        $user = User::factory()->create();
        Auth::login($user);
        $bpmn = file_get_contents(__DIR__ . '/../Fixtures/script_without_settings.bpmn');
        $bpmn = str_replace('[script_id]', $scriptId, $bpmn);
        $process = Process::factory()->create([
            'bpmn' => $bpmn,
        ]);
        $process->manager_id = $user->id;
        $process->save();

        $request = ProcessRequest::factory()->create([
            'process_id' => $process->id,
        ]);
        $token = ProcessRequestToken::factory()->create([
            'process_request_id' => $request->id,
            'element_id' => 'node_2',
            'status' => 'ACTIVE',
        ]);

        if ($class === RunScriptTask::class) {
            $class::dispatch($process, $request, $token, []);
        } else {
            $class::dispatch($token);
        }

        return $request->refresh();
    }

    public function jobTypes()
    {
        return [
            [RunScriptTask::class],
            [RunNayraScriptTask::class],
        ];
    }
}
