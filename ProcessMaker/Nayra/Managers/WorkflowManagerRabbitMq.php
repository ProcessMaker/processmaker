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
    const ACTION_START_PROCESS = 'START_PROCESS';

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

        // Create inmediatly a new process request
        $request = ProcessRequest::create([
            'process_id' => $definitions->id,
            'user_id' => $userId,
            'callable_id' => $event->getProcess()->getId(),
            'status' => 'ACTIVE',
            'data' => $data,
            'name' => $definitions->name,
            'do_not_sanitize' => [],
            'initiated_at' => Carbon::now(),
            'process_version_id' => $version->getKey(),
            'signal_events' => [],
        ]);

        // Create triggered
        // TO DO:

        // Dispatch start process event
        $this->dispatchAction([
            'bpmn' => $version->getKey(),
            'action' => self::ACTION_START_PROCESS,
            'params' => [
                'instance_id' => $request->uuid,
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
                    $request->uuid => [
                        'id' => $request->uuid,
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
    private function dispatchAction(array $action): void
    {
        $subject = $action['action'];
        $thread = $action['collaboration_id'] ?? 0;
        MessageBrokerService::sendMessage($subject, $thread, $action);
    }
}
