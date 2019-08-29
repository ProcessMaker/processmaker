<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Testing\File;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;

trait TestProcessExecutionTrait
{

    /**
     * @var Process $process
     */
    protected $process;

    /**
     * Create new task assignment type user successfully
     */
    private function loadTestProcess($bpmn, array $users = [])
    {
        // Create a new process
        $this->process = factory(Process::class)->create([
            'bpmn' => $bpmn,
        ]);

        $definitions = $this->process->getDefinitions();
        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'task') as $task) {
            if ($task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment') === 'user') {
                $userId = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers');
                if (isset($users[$userId])) {
                    $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers', $users[$userId]->id);
                } elseif (!User::find($userId)) {
                    $users[$userId] = factory(User::class)->create([
                        'id' => $userId,
                        'status' => 'ACTIVE',
                    ]);
                    $users[$userId] =
                    $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers', $users[$userId]->id);
                }
            }
        }
        $this->process->bpmn = $definitions->saveXml();
        // When save the process creates the assignments
        $this->process->save();
    }
}
