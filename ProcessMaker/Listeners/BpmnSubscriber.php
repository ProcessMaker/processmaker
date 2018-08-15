<?php
namespace ProcessMaker\Listeners;

use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Bpmn\Events\ProcessInstanceCreatedEvent;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Notifications\ActivityActivatedNotification;
use ProcessMaker\Nayra\Bpmn\Events\ProcessInstanceCompletedEvent;
use ProcessMaker\Notifications\ProcessCompletedNotification;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Facades\WorkflowManager;

/**
 * Description of BpmnSubscriber
 *
 */
class BpmnSubscriber
{

    /**
     * When a new activity is Activated
     *
     * @param ActivityActivatedEvent $event
     */
    public function ActivityActivated(ActivityActivatedEvent $event)
    {
        $token = $event->token;
        Log::info('Nofity activity activated: ' . json_encode($token->getProperties()));

        //Send the notification to the assigned user
        $user = $event->token->user;
        if (!empty($user)) {
            $notification = new ActivityActivatedNotification($event->token);
            $user->notify($notification);
        }
    }

    /**
     * When a process instance is completed.
     *
     * @param ProcessInstanceCreatedEvent $event
     */
    public function ProcessCompleted(ProcessInstanceCompletedEvent $event)
    {
        //client events
        $user = $event->instance->creator;
        $notification = new ProcessCompletedNotification($event->instance);
        $user->notify($notification);

        Log::info('ProcessCompleted: ' . json_encode($event->instance->getProperties()));
    }

    /**
     * When a process instance is created.
     *
     * @param ProcessInstanceCreatedEvent $event
     */
    public function onProcessCreated(ProcessInstanceCreatedEvent $event)
    {
        Log::info('ProcessCreated: ' . json_encode($event->instance->getProperties()));
    }

    /**
     * When an activity is activated.
     *
     * @param ActivityActivatedEvent $event
     */
    public function onActivityActivated(ActivityActivatedEvent $event)
    {
        Log::info('ActivityActivated: ' . json_encode($event->token->getProperties()));
        $this->ActivityActivated($event);
    }

    /**
     * When the user completes a task.
     *
     * @param $event
     */
    public function onActivityCompleted(ActivityCompletedEvent $event)
    {
        Log::info('ActivityCompleted: ' . json_encode($event->token->getProperties()));
    }

    /**
     * When the activity is closed.
     *
     * @param $event
     */
    public function onActivityClosed(ActivityClosedEvent $event)
    {
        Log::info('ActivityClosed: ' . json_encode($event->token->getProperties()));
    }

    /**
     * When the activity is closed.
     *
     * @param $event
     */
    public function onScriptTaskActivated(ScriptTaskInterface $scriptTask, TokenInterface $token)
    {
        Log::info('ScriptTaskActivated: ' . $scriptTask->getId());
        WorkflowManager::runScripTask($scriptTask, $token);
    }

    /**
     * Subscription.
     *
     * @param type $events
     */
    public function subscribe($events)
    {
        $events->listen(ProcessInterface::EVENT_PROCESS_INSTANCE_CREATED, static::class . '@onProcessCreated');
        $events->listen(ProcessInterface::EVENT_PROCESS_INSTANCE_COMPLETED, static::class . '@ProcessCompleted');

        $events->listen(ActivityInterface::EVENT_ACTIVITY_COMPLETED, static::class . '@onActivityCompleted');
        $events->listen(ActivityInterface::EVENT_ACTIVITY_CLOSED, static::class . '@onActivityClosed');

        $events->listen(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, static::class . '@onActivityActivated');
        $events->listen(ScriptTaskInterface::EVENT_SCRIPT_TASK_ACTIVATED, static::class . '@onScriptTaskActivated');
    }
}
