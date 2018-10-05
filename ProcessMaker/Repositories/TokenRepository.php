<?php
namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;
use ProcessMaker\Repositories\ExecutionInstanceRepository;

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
        return new Token();
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
        $user = $token->getInstance()->process->getNextUser($activity);
        $token->uuid_text = $token->getId();
        $token->status = $token->getStatus();
        $token->element_uuid = $activity->getId();
        $token->element_type = $activity instanceof ScriptTaskInterface ? 'scriptTask' : 'task';
        $token->element_name = $activity->getName();
        $token->process_uuid = $token->getInstance()->process->uuid;
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->user_uuid = $user ? $user->uuid : null;
        //Default 3 days of due date
        $due = $activity->getProperty('dueDate', '72');
        $token->due_at = $due ? Carbon::now()->addHours($due) : null;
        $token->initiated_at = null;
        $token->riskchanges_at = $due ? Carbon::now()->addHours($due * 0.7) : null;
        $token->saveOrFail();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
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
        $token->uuid_text = $token->getId();
        $token->status = $token->getStatus();
        $token->element_uuid = $activity->getId();
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->completed_at = Carbon::now();
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
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
        $token->uuid_text = $token->getId();
        $token->status = $token->getStatus();
        $token->element_uuid = $activity->getId();
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $token->uuid_text = $token->getId();
        $token->status = $token->getStatus();
        $token->element_uuid = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_uuid = $token->getInstance()->process->uuid;
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->user_uuid = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $token->uuid_text = $token->getId();
        $token->status = 'CLOSED';
        $token->element_uuid = $intermediateCatchEvent->getId();
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->completed_at = Carbon::now();
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventMessageArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $token->uuid_text = $token->getId();
        $token->status = $token->getStatus();
        $token->element_uuid = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_uuid = $token->getInstance()->process->uuid;
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->user_uuid = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->saveOrFail();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventMessageConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $token->uuid_text = $token->getId();
        $token->status = 'CLOSED';
        $token->element_uuid = $intermediateCatchEvent->getId();
        $token->process_request_uuid = $token->getInstance()->uuid;
        $token->completed_at = Carbon::now();
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, Collection $consumedTokens)
    {
        
    }

    public function persistGatewayTokenArrives(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
        
    }

    public function persistGatewayTokenConsumed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
        
    }

    public function persistGatewayTokenPassed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
        
    }

    public function persistThrowEventTokenArrives(ThrowEventInterface $event, TokenInterface $token)
    {
        
    }

    public function persistThrowEventTokenConsumed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
        
    }

    public function persistThrowEventTokenPassed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
        
    }

    public function store(TokenInterface $token, $saveChildElements = false): \this
    {
        
    }
}
