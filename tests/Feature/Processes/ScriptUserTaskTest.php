<?php

namespace Tests\Feature;

use ProcessMaker\Exception\ScriptTaskWithoutUser;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;

class ScriptsUserTaskTest extends TestCase
{
    use RequestHelper;

    public function testJobThatRunsAScriptTaskWithAUser()
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasksWithUser.bpmn');
        $process = factory(Process::class)->create($data);

        $user = factory(User::class)->create();

        factory(Script::class)->create([
            'id' => 10,
            'title' => 'titletest',
            'run_as_user_id' => $user->id
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
