<?php

namespace ProcessMaker\Nayra\Managers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Contracts\WorkflowManagerInterface;
use ProcessMaker\Facades\MessageBrokerService;
use ProcessMaker\Models\Process as Definitions;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;

class WorkflowManagerRabbitMq extends WorkflowManagerDefault implements WorkflowManagerInterface
{
	/**
     * Trigger a start event and return the process request instance.
     *
     * @param Definitions $definitions
     * @param StartEventInterface $event
     * @param array $data
     * @return ProcessRequest
     */
    public function triggerStartEvent(Definitions $definitions, StartEventInterface $event, array $data): ProcessRequest
    {
        // Validate data
        $this->validateData($data, $definitions, $event);

        // Get complementary information
        $version = $definitions->getLatestVersion();
        $userId = $this->getCurrentUserId();

        // Generate UID
        $uid = makeUuid();

        // Create inmediatly a new process request
        $request = ProcessRequest::create([
            'uuid' => $uid,
            'callable_id' => $event->getProcess()->getId(),
            'process_id' => $definitions->id,
            'process_version_id' => $version->getKey(),
            'user_id' => $userId,
            'name' => $definitions->name,
            'status' => 'ACTIVE',
            'initiated_at' => Carbon::now(),
            'data' => $data,
        ]);

        // Dispatch start process event
        $this->dispatchAction([
            'bpmn' => $version->getKey(),
            'action' => 'START_PROCESS',
            'params' => [
                'instance_id' => $uid,
                'request_id' => $request->getKey(),
                'element_id' => $event->getId(),
                'data'=> $data,
                'extra_properties' => [
                    'user_id' => $userId,
                    'process_id' => $definitions->id,
                    'request_id' => $request->getKey(),
                ],
            ],
            'state' => [
                'requests' => [
                    $uid => [
                        'id' => $uid,
                        'callable_id' => $request->callable_id,
                        'data' => $request->data,
                        'tokens' => [],
                    ]
                ],
            ],
        ]);

        return $request;
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    private function getCurrentUserId(): ? int
    {
        // Get the id from the current user
        $webGuardId = Auth::id();
        $apiGuardId = Auth::guard('api')->id();

        return $webGuardId ?? $apiGuardId;
    }

    /**
     * Send payload
     *
     * @param array $action
     */
    private function dispatchAction(array $action)
    {
        $subject = $action['action'];
        $thread = $action['collaboration_id'] ?? 0;
        MessageBrokerService::sendMessage($subject, $thread, $action);
    }
}
