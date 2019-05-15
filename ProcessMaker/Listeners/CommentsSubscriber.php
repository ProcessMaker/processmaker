<?php
namespace ProcessMaker\Listeners;

use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;

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
     * Subscription.
     *
     * @param $events
     */
    public function subscribe($events)
    {
        $events->listen(ActivityInterface::EVENT_ACTIVITY_COMPLETED, static::class . '@onActivityCompleted');
    }
}
