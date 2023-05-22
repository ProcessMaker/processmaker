<?php

namespace Tests\Feature\Shared;

use DOMElement;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Providers\WorkflowServiceProvider;

trait ProcessTestingTrait
{
    /**
     * Create new process from a BPMN
     *
     * @return Process
     */
    private function createProcess($attributes, array $users = [])
    {
        $attributes = is_string($attributes) ? ['bpmn' => $attributes] : $attributes;
        // Create a new process
        $process = Process::factory()->create($attributes);

        $definitions = $process->getDefinitions();
        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'task') as $task) {
            $this->assignTaskUser($task, $users);
        }
        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'userTask') as $task) {
            $this->assignTaskUser($task, $users);
        }
        foreach ($definitions->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'manualTask') as $task) {
            $this->assignTaskUser($task, $users);
        }
        $process->bpmn = $definitions->saveXml();
        // When save the process creates the assignments
        $process->save();

        return $process;
    }

    /**
     * Assign or create an user for a task
     *
     * @param DOMElement $task
     * @param array $users
     */
    private function assignTaskUser(DOMElement $task, array &$users)
    {
        $assignment = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignment');
        if ($assignment === 'user' || $assignment === 'user_group') {
            $userId = $task->getAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers');
            if (!$userId) {
                return;
            }
            if (isset($users[$userId])) {
                $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers', $users[$userId]->id);
            } elseif (!User::find($userId)) {
                $users[$userId] = User::factory()->create([
                    'id' => $userId,
                    'status' => 'ACTIVE',
                ]);
                $users[$userId] =
                $task->setAttributeNS(WorkflowServiceProvider::PROCESS_MAKER_NS, 'assignedUsers', $users[$userId]->id);
            }
        }
    }

    /**
     * Star a process request
     *
     * @param Process $process
     * @param string $startEvent
     * @param array $data
     *
     * @return ProcessRequest
     */
    private function startProcess(Process $process, $startEvent, array $data = [])
    {
        // Start a process request
        $route = route('api.process_events.trigger', [$process->getKey(), 'event' => $startEvent]);
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();

        return ProcessRequest::find($requestJson['id']);
    }

    /**
     * Complete a task by $token
     *
     * @param ProcessRequestToken $token
     * @param array $data
     *
     * @return ProcessRequestToken|\Illuminate\Foundation\Testing\TestResponse
     */
    private function completeTask(ProcessRequestToken $token, array $data = [], $return = 'token')
    {
        $route = route('api.tasks.update', [$token->getKey()]);
        $response = $this->apiCall('PUT', $route, [
            'status' => 'COMPLETED',
            'data' => $data,
        ]);
        if ($return === 'response') {
            return $response;
        } else {
            $json = $response->json();
            if ($json && isset($json['id'])) {
                return ProcessRequestToken::find($response->json()['id']);
            } else {
                throw new \Exception($response->getContents());
            }
        }
    }

    /**
     * Trigger a catch event
     *
     * @param ProcessRequestToken $token
     * @param array $data
     * @return void
     */
    private function triggerCatchEvent(ProcessRequestToken $token, array $data = [])
    {
        WorkflowManager::completeCatchEvent($token->processRequest->process, $token->processRequest, $token, $data);
    }

    /**
     * Log the execution events
     */
    private function logExecutionEvents()
    {
        app('events')->listen('*', function ($e) {
            preg_replace('/\W/', '', $e) != $e ?: error_log($e);
        });
    }

    /**
     * Run scheduled tasks
     */
    private function runScheduledTasks()
    {
        $schedule = app()->make(Schedule::class);
        $scheduleManager = new TaskSchedulerManager();
        $scheduleManager->scheduleTasks();
        ///
        $events = collect($schedule->events());
        $events->each(function (Event $event) {
            $event->isDue(app()) && $event->filtersPass(app()) ? $event->run(app()) : null;
        });
    }

    /**
     * Restore the fake date for TaskSchedulerManager
     */
    protected function teardownProcessTestingTrait()
    {
        TaskSchedulerManager::fakeToday(null);
    }
}
