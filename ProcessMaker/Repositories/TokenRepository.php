<?php
namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Model\Delegation as Token;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
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
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->user_id = Auth::id();
        $token->delegate_date = Carbon::now();
        //@todo calculate the due date
        $token->task_due_date = Carbon::now()->addDays(3);
        $token->started = false;
        $token->finished = false;
        $token->delayed = false;
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
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
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->user_id = Auth::id();
        $token->started = true;
        $token->finished = true;
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
        $token->uid = $token->getId();
        $token->thread_status = $token->getStatus();
        $token->element_ref = $activity->getId();
        $token->application_id = $token->getInstance()->id;
        $token->save();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        
    }

    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        
    }

    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
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
