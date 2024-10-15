<?php

namespace ProcessMaker\Listeners;

use ProcessMaker\Models\Comment;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;

/**
 * Description of BpmnSubscriber
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
        $token = $event->token;
        $user_id = $token->user ? $token->user_id : null;
        $user_name = $token->user ? $token->user->fullname : __('The System');

        if (!is_int($token->process_request_id)) {
            return;
        }

        $message = ':user has completed the task :task_name';
        if ($token->is_actionbyemail) {
            $message = $message . ' via email';
        }
        // Get the case_number
        $caseNumber = ProcessRequest::where('id', $token->process_request_id)->value('case_number');

        Comment::create([
            'type' => 'LOG',
            'user_id' => $user_id,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $token->process_request_id,
            'subject' => 'Task Complete',
            'body' => __($message, ['user' => $user_name, 'task_name' => $token->element_name]),
            'case_number' => $caseNumber,
        ]);
    }

    /**
     * When a task is skipped.
     *
     * ex. When a MultiInstance Task with Empty Input Items
     *
     * @param $event
     */
    public function onActivitySkipped(ActivityInterface $activity, ProcessRequestToken $token)
    {
        $taskName = $token->getOwnerElement()->getName();

        if (!is_int($token->getInstance()->getId())) {
            return;
        }
        // Get the case number
        $caseNumber = ProcessRequest::where('id', $token->getInstance()->getId())->value('case_number');

        Comment::create([
            'type' => 'LOG',
            'user_id' => null,
            'commentable_type' => ProcessRequest::class,
            'commentable_id' => $token->getInstance()->getId(),
            'subject' => 'Task Skipped',
            'body' => __('The task :task_name was skipped', ['task_name' => $taskName]),
            'case_number' => $caseNumber,
        ]);
    }

    /**
     * @param $gateway
     * @param $tokens
     * @param $transition
     */
    public function onGatewayPassed($gateway, $transition = null, $tokens = null)
    {
        if ($transition === null || $tokens === null) {
            return;
        }
        $flows = collect($gateway->getProperties()['outgoing']->toArray());

        // Find the flows that corresponds to the transition condition
        // If the flow does not have a condition Expressions, it is an inclusive
        // gateway transition so that it is used.
        $usedFlows = $flows->filter(function ($flow) use ($transition) {
            return $transition->outgoing()->item(0)->target()->getOwner()->getProperty('id')
                === $flow->getProperties()['target']->getProperty('id');
        });

        if (count($usedFlows) === 0) {
            return;
        }

        // wee need just one token to get the user data
        $token = $tokens->item(0);

        foreach ($usedFlows as $flow) {
            $user_id = $token->user ? $token->user_id : null;
            $flowProps = $flow->getProperties();
            $sourceProps = $gateway->getProperties();
            $sourceLabel = array_key_exists('name', $sourceProps) && $sourceProps['name']
                ? $sourceProps['name']
                : __('Gateway');

            $flowLabel = array_key_exists('name', $flowProps) && $flowProps['name']
                ? $flowProps['name']
                : __('Label Undefined');

            if (!is_int($token->getInstance()->getId())) {
                return;
            }
            // Get the case number
            $caseNumber = ProcessRequest::where('id', $token->getInstance()->getId())->value('case_number');

            Comment::create([
                'type' => 'LOG',
                'user_id' => $user_id,
                'commentable_type' => ProcessRequest::class,
                'commentable_id' => $token->getInstance()->getId(),
                'subject' => 'Gateway',
                'body' => $sourceLabel . ': ' . $flowLabel,
                'case_number' => $caseNumber,
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
        $events->listen(ActivityInterface::EVENT_ACTIVITY_SKIPPED, static::class . '@onActivitySkipped');
        $events->listen(GatewayInterface::EVENT_GATEWAY_TOKEN_PASSED, static::class . '@onGatewayPassed');
    }
}
