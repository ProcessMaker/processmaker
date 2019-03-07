<?php

namespace Tests\Feature;

use ProcessMaker\Exception\ScriptTaskWithoutUser;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Jobs\CompleteActivity;
use ProcessMaker\Jobs\RunScriptTask;
use ProcessMaker\Models\Process;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Shared\RequestHelper;

class ScriptsUserTaskTest extends TestCase
{
    use RequestHelper;
    


    public function testRunScriptTaskWithUser()
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasksWithUser.bpmn');
        $process = factory(Process::class)->create($data);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);

        $request->refresh();

        // If no exception has been thrown, this assertion will be executed
        $this->assertTrue(true);
    }

    public function testJobThatRunsAScriptTaskWithoutAUser()
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasksWithUser.bpmn');
        $process = factory(Process::class)->create($data);
        $definitions = $process->getDefinitions();
        $startEvent = $definitions->getEvent('node_2');
        $request = WorkflowManager::triggerStartEvent($process, $startEvent, []);
        $task1 = $request->tokens()->where('element_id', 'node_16')->first();

        $this->expectException(ScriptTaskWithoutUser::class);

        $job = new CompleteActivity($process, $request, $task1, []);
        $job->handle();
    }

    public function testJobThatRunsAScriptTaskWithAUser()
    {
        $data = [];
        $data['bpmn'] = Process::getProcessTemplate('ScriptTasksWithUser.bpmn');
        $process = factory(Process::class)->create($data);
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
