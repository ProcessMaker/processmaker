<?php

namespace Tests\Feature\Processes;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class ScriptUserTaskTest extends TestCase
{
    use RequestHelper;

    public function testJobThatRunsAScriptTaskWithAUser()
    {
        $this->markTestSkipped();

        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasksWithUser.bpmn');
        $process = Process::factory()->create($data);

        $user = User::factory()->create();
        $this->be($user);

        Script::factory()->create([
            'id' => 10,
            'title' => 'titletest',
            'run_as_user_id' => $user->id,
        ]);

        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);
        $task1 = $request->tokens()->where('element_id', 'node_16')->first();

        $job = new CompleteActivity($process, $request, $task1, []);
        $job->handle();

        // at this point, no error should have been thrown
        $this->assertTrue(true);
    }
}
