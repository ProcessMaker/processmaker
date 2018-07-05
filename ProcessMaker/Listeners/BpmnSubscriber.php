<?php
namespace ProcessMaker\Listeners;

use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Bpmn\Events\ProcessInstanceCreatedEvent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use ProcessMaker\Notifications\ActivityActivatedNotification;

/**
 * Description of BpmnSubscriber
 *
 */
class BpmnSubscriber
{

    public function ActivityActivated(ActivityActivatedEvent $event)
    {
        $token = $event->token;
        Log::info('Nofity activity activated: ' . json_encode($token->getProperties()));

        //client events
        $user = Auth::user();
        $notification = new ActivityActivatedNotification($event->token);
        $user->notify($notification);
    }

    /**
     * When a process instance is created.
     *
     * @param ProcessInstanceCreatedEvent $event
     */
    public function onProcessCreated(ProcessInstanceCreatedEvent $event)
    {
        //Get references

        $this->saveProcessInstance($event->instance);

        Log::info('ProcessCreated: ' . json_encode($event->instance->getProperties()));
    }
    
    private function saveProcessInstance(\ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface $instance)
    {
        $instance->uid = $instance->getId();
        $callable = $instance->getProcess();
        //Get application data from engine
        $data = $instance->getDataStore()->getData();
        
        //Save the row
        $instance->callable = $callable->getId();
        $instance->process_id = $callable->getEngine()->getProcess()->id;
        $instance->APP_TITLE = '';
        $instance->creator_user_id = 1;
        $instance->APP_INIT_DATE = Carbon::now();
        $instance->APP_DATA = json_encode($data);
        $instance->save();
    }

    /**
     * When an activity is activated.
     *
     * @param ActivityActivatedEvent $event
     */
    public function onActivityActivated(ActivityActivatedEvent $event)
    {
        $token = $event->token;
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $event->activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->user_id = Auth::id();
        $token->delegate_date = Carbon::now();
        $token->started = false;
        $token->finished = false;
        $token->delayed = false;
        $token->save();
        $this->saveProcessInstance($token->getInstance());

        Log::info('ActivityActivated: ' . json_encode($token->getProperties()));
        $this->ActivityActivated($event);
    }

    /**
     * When the user completes a task.
     *
     * @param $event
     */
    public function onActivityCompleted(ActivityCompletedEvent $event)
    {
        $token = $event->token;
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $event->activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->user_id = Auth::id();
        $token->started = true;
        $token->finished = true;
        $token->save();
        $this->saveProcessInstance($token->getInstance());

        Log::info('ActivityCompleted: ' . json_encode($token->getProperties()));
    }

    /**
     * When the activity is closed.
     *
     * @param $event
     */
    public function onActivityClosed(ActivityClosedEvent $event)
    {
        $token = $event->token;
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $event->activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->save();
        $this->saveProcessInstance($token->getInstance());

        Log::info('ActivityClosed: ' . json_encode($token->getProperties()));
    }

    /**
     * Subscription.
     *
     * @param type $events
     */
    public function subscribe($events)
    {
        $events->listen(ProcessInterface::EVENT_PROCESS_INSTANCE_CREATED, static::class . '@onProcessCreated');

        $events->listen(ActivityInterface::EVENT_ACTIVITY_COMPLETED, static::class . '@onActivityCompleted');
        $events->listen(ActivityInterface::EVENT_ACTIVITY_CLOSED, static::class . '@onActivityClosed');

        $events->listen(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, static::class . '@onActivityActivated');
    }
}
