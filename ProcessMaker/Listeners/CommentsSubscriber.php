<?php
namespace ProcessMaker\Listeners;

use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;

/**
 * Description of BpmnSubscriber
 *
 */
class CommentsSubscriber
{

    /**
     * When the user completes a task.
     *
     * @param $event
     */
    public function onActivityCompleted(ActivityCompletedEvent $event)
    {
        $token     = $event->token;
        $user_id   = $token->user ? $token->user_id : null;
        $user_name = $token->user ? $token->user->fullname : __('The System');
        Comment::create([
            'type' => 'LOG',
            'user_id' => $user_id,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $token->process_request_id,
            'subject' => 'Task Complete',
            'body' => __(":user has completed the task :task_name", ['user' => $user_name, 'task_name' => $token->element_name]),
        ]);
    }

    /**
     * When the an activity is activated
     *
     * @param $event
     */
    public function onActivityActivated(ActivityActivatedEvent $event)
    {
        $token     = $event->token;
        $user_id   = $token->user ? $token->user_id : null;
        $user_name = $token->user ? $token->user->fullname : __('The System');

        $incomingArray = $event->activity->getProperties()["incoming"];
        $incomingFlow = $incomingArray->item(0);

        $flowSource = $incomingFlow->getProperties()["source"];
        if ($flowSource instanceof GatewayInterface) {
            $properties = $incomingFlow->getProperties();
            $flowLabel = array_key_exists('name', $properties) && $properties['name']
                        ? $properties['name']
                        : __('Label Undefined');
            Comment::create([
                'type' => 'LOG',
                'user_id' => $user_id,
                'commentable_type' => ProcessRequest::class,
                'commentable_id' => $token->process_request_id,
                'subject' => 'Gateway',
                'body' => __('Gateway: :flow_label', ['flow_label' => $flowLabel]),
            ]);
        }
    }


    /**
     * Subscription.
     *
     * @param $events
     */
    public function subscribe($events)
    {
        $events->listen(ActivityInterface::EVENT_ACTIVITY_COMPLETED, static::class . '@onActivityCompleted');
        $events->listen(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, static::class . '@onActivityActivated');
    }
}
