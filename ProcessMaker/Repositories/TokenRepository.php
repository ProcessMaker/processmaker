<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Bpmn\Models\EndEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventBasedGatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;

/**
 * Execution Instance Repository.
 *
 * @package ProcessMaker\Models
 */
class TokenRepository implements TokenRepositoryInterface
{
    /**
     * @var ExecutionInstanceRepository
     */
    private $instanceRepository;

    /**
     * Initialize the Token Repository.
     *
     * @param ExecutionInstanceRepository $instanceRepository
     */
    public function __construct(ExecutionInstanceRepository $instanceRepository)
    {
        $this->instanceRepository = $instanceRepository;
    }

    /**
     * Creates an instance of Token.
     *
     * @return \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface
     */
    public function createTokenInstance(): TokenInterface
    {
        $token = new Token();
        $token->setId(uniqid('request', true));
        return $token;
    }

    public function loadTokenByUid($uid): TokenInterface
    {
    }

    /**
     * Persists instance and token data when a token arrives to an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityActivated(ActivityInterface $activity, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $user = $token->getInstance()->process->getNextUser($activity, $token);
        $this->addUserToData($token->getInstance(), $user);
        $this->addRequestToData($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->element_type = $this->getActivityType($activity);
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = $user ? $user->getKey() : null;
        $token->is_self_service = $token->getAssignmentRule() === 'self_service' ? 1 : 0;
        $selfServiceTasks = $token->processRequest->processVersion->self_service_tasks;
        $token->self_service_groups = $selfServiceTasks && isset($selfServiceTasks[$activity->getId()]) ? $selfServiceTasks[$activity->getId()] : [];
        //Default 3 days of due date
        $due = $activity->getProperty('dueIn', '72');
        $token->due_at = $due ? Carbon::now()->addHours($due) : null;
        $token->initiated_at = null;
        $token->riskchanges_at = $due ? Carbon::now()->addHours($due * 0.7) : null;
        $token->saveOrFail();
        $token->setId($token->getKey());
        event(new ProcessUpdated($token->getInstance(), 'ACTIVITY_ACTIVATED'));
    }

    /**
     * Persists tokens that triggered a Start Event
     *
     * @param StartEventInterface $startEvent
     * @param CollectionInterface $tokens
     *
     * @return mixed
     */
    public function persistStartEventTriggered(StartEventInterface $startEvent, CollectionInterface $tokens)
    {
        $token = $tokens->item(0);

        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'TRIGGERED';
        $token->element_id = $startEvent->getId();
        $token->element_type = $startEvent instanceof StartEventInterface ? 'startEvent' : 'task';
        $token->element_name = $startEvent->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = empty(Auth::user()) ? null : Auth::user()->id;

        $token->data = $token->getInstance()->getDataStore()->getData();
        $token->due_at = null;
        $token->initiated_at = Carbon::now();
        $token->completed_at = Carbon::now();
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $token->setId($token->getKey());
        event(new ProcessUpdated($token->getInstance(), 'START_EVENT_TRIGGERED'));
    }

    private function assignTaskUser(ActivityInterface $activity, TokenInterface $token, Instance $instance)
    {
    }

    /**
     * Persists instance and token data when a token within an activity change to error state
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityException(ActivityInterface $activity, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceError($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->element_type = $this->getActivityType($activity);
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->save();
        $token->setId($token->getKey());
        event(new ProcessUpdated($token->getInstance(), 'ACTIVITY_EXCEPTION'));
    }

    /**
     * Persists instance and token data when a token is completed within an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityCompleted(ActivityInterface $activity, TokenInterface $token)
    {
        $this->removeUserFromData($token->getInstance());
        $this->removeRequestFromData($token->getInstance());
        if ($this->getActivityType($activity) === 'callActivity') {
            $this->removeParentFromData($token->getInstance());
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->save();
        $token->setId($token->getKey());
        event(new ProcessUpdated($token->getInstance(), 'ACTIVITY_COMPLETED'));
    }

    /**
     * Persists instance and token data when a token is closed by an activity
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return mixed
     */
    public function persistActivityClosed(ActivityInterface $activity, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->data = $token->getInstance()->getDataStore()->getData();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $token->setId($token->getKey());
        $token->getInstance()->updateCatchEvents();
    }

    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $intermediateCatchEvent->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventMessageArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $token->setId($token->getKey());
    }

    public function persistCatchEventMessageConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $intermediateCatchEvent->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, Collection $consumedTokens)
    {
    }

    public function persistGatewayTokenArrives(GatewayInterface $gateway, TokenInterface $token)
    {
        if ($token->exists) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_index = $token->getIndex();
        $token->element_id = $gateway->getId();
        $token->element_type = 'gateway';
        $token->element_name = $gateway->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $token->setId($token->getKey());
    }

    public function persistGatewayTokenConsumed(GatewayInterface $gateway, TokenInterface $token)
    {
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $gateway->getId();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistGatewayTokenPassed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    public function persistThrowEventTokenArrives(ThrowEventInterface $event, TokenInterface $token)
    {
    }

    public function persistThrowEventTokenConsumed(ThrowEventInterface $event, TokenInterface $token)
    {
        // we register just end event throw events
        if ($event instanceof EndEvent) {
            $this->instanceRepository->persistInstanceUpdated($token->getInstance());
            $token->status = 'CLOSED';
            $token->element_id = $event->getId();
            $token->element_type = 'end_event';
            $token->element_name = $event->getName();
            $token->process_id = $token->getInstance()->process->getKey();
            $token->process_request_id = $token->getInstance()->getKey();
            $token->user_id = null;
            $token->due_at = null;
            $token->riskchanges_at = null;
            $token->completed_at = Carbon::now();
            $token->saveOrFail();
            $token->setId($token->getKey());
        }
    }

    public function persistThrowEventTokenPassed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    public function store(TokenInterface $token, $saveChildElements = false): \this
    {
    }

    /**
     * Add user to the request data.
     *
     * @param Instance $instance
     * @param User $user
     */
    private function addUserToData(Instance $instance, User $user = null)
    {
        if (empty($user)) {
            $instance->getDataStore()->putData('_user', null);
        } else {
            $userData = $user->toArray();
            unset($userData['remember_token']);
            $instance->getDataStore()->putData('_user', $userData);
        }
        $this->instanceRepository->persistInstanceUpdated($instance);
    }

    /**
     * Remove user from the request data.
     *
     * @param Instance $instance
     * @param User $user
     */
    private function removeUserFromData(Instance $instance)
    {
        $instance->getDataStore()->removeData('_user');
    }

    /**
     * Add request to the request data.
     *
     * @param Instance $instance
     */
    private function addRequestToData(Instance $instance)
    {
        if (!$instance->getDataStore()->getData('_request')) {
            $instance->getDataStore()->putData('_request', $instance);
            $this->instanceRepository->persistInstanceUpdated($instance);
        }
    }

    /**
     * Remove request from the request data.
     *
     * @param Instance $instance
     */
    private function removeRequestFromData(Instance $instance)
    {
        $instance->getDataStore()->removeData('_request');
    }

    /**
     * Remove _parent magic variable from the request data.
     *
     * @param Instance $instance
     */
    private function removeParentFromData(Instance $instance)
    {
        $instance->getDataStore()->removeData('_parent');
    }


    private function getActivityType($activity)
    {
        if ($activity instanceof  ScriptTaskInterface) {
            return 'scriptTask';
        }

        if ($activity instanceof  CallActivityInterface) {
            return 'callActivity';
        }

        if ($activity instanceof  ActivityInterface) {
            return 'task';
        }

        return 'task';
    }

    /**
     * Persists a Call Activity Activated
     *
     * @param TokenInterface $token
     * @param ExecutionInstanceInterface $subprocess
     * @return void
     */
    public function persistCallActivityActivated(TokenInterface $token, ExecutionInstanceInterface $subprocess, FlowInterface $sequenceFlow)
    {
        $source = $token->getInstance();
        if ($source->process_collaboration_id === null) {
            $collaboration = new ProcessCollaboration();
            $collaboration->process_id = $source->process->getKey();
            $collaboration->saveOrFail();
            $source->process_collaboration_id = $collaboration->getKey();
            $source->saveOrFail();
        }
        $subprocess->user_id = $token->user_id;
        $subprocess->process_collaboration_id = $source->process_collaboration_id;
        $subprocess->parent_request_id = $source->getKey();
        $subprocess->saveOrFail();
        $token->subprocess_request_id = $subprocess->id;
        $token->subprocess_start_event_id = $sequenceFlow->getProperty('startEvent');
        $token->saveOrFail();
    }

    /**
     * Persists instance and token data when a token is consumed in a event based gateway
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\EventBasedGatewayInterface $eventBasedGateway
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface $passedToken
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface $consumedTokens
     *
     * @return mixed
     */
    public function persistEventBasedGatewayActivated(EventBasedGatewayInterface $eventBasedGateway, TokenInterface $passedToken, CollectionInterface $consumedTokens)
    {
        Log::info('persistEventBasedGatewayActivated');
    }
}
