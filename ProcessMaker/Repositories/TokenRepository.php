<?php

namespace ProcessMaker\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mustache_Engine;
use ProcessMaker\Mail\TaskActionByEmail;
use ProcessMaker\Models\ProcessAbeRequestToken;
use ProcessMaker\Models\ProcessCollaboration;
use ProcessMaker\Models\ProcessRequest as Instance;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessRequestToken as Token;
use ProcessMaker\Models\User;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Bpmn\Models\EndEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventBasedGatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;

/**
 * Execution Instance Repository.
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

    public function loadTokenByUid($uid): ? TokenInterface
    {
        if (is_numeric($uid)) {
            return Token::find($uid);
        }

        return Token::where('uuid', $uid)->first();
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
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $token->status = ActivityInterface::TOKEN_STATE_ACTIVE;
        $token->element_id = $activity->getId();
        $token->element_type = $this->getActivityType($activity);
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $isScriptOrServiceTask = $activity instanceof ScriptTaskInterface || $activity instanceof ServiceTaskInterface;
        if ($isScriptOrServiceTask) {
            $user = null;
        } else {
            $user = $token->getInstance()->getProcess()->getOwnerDocument()->getModel()->getNextUser($activity, $token);
        }
        $this->addUserToData($token->getInstance(), $user);
        $this->addRequestToData($token->getInstance());
        $token->user_id = $user ? $user->getKey() : null;

        $token->is_self_service = 0;
        if ($token->getAssignmentRule() === 'self_service' || $token->getSelfServiceAttribute()) {
            if ($user) {
                // A user is already assigned (from assignmentLock) so do not
                // treat this as a self-service task
                $token->is_self_service = 0;
            } else {
                $token->is_self_service = 1;
            }
        }

        $selfServiceTasks = $token->getInstance()->processVersion->self_service_tasks;
        $token->self_service_groups = $selfServiceTasks && isset($selfServiceTasks[$activity->getId()]) ? $selfServiceTasks[$activity->getId()] : [];

        if ($token->getSelfServiceAttribute()) {
            $selfServiceUsers = !empty($token->getDefinition()['assignedUsers']) ? $token->getDefinition()['assignedUsers'] : [];
            $selfServiceGroups = !empty($token->getDefinition()['assignedGroups']) ? $token->getDefinition()['assignedGroups'] : [];

            switch ($activity->getProperty('assignment')) {
                case 'user_group':
                    // For this assignment type, users and groups arrive as comma separated numbers that
                    // we need to convert to arrays for the self_service_groups column
                    $evaluatedUsers = $selfServiceUsers ? explode(',', $selfServiceUsers) : [];
                    $evaluatedGroups = $selfServiceGroups ? explode(',', $selfServiceGroups) : [];
                    $token->self_service_groups = ['users' => $evaluatedUsers, 'groups' => $evaluatedGroups];
                    break;
                case 'process_variable':
                    $evaluatedUsers = $selfServiceUsers ? $token->getInstance()->getDataStore()->getData($selfServiceUsers) : [];
                    $evaluatedGroups = $selfServiceGroups ? $token->getInstance()->getDataStore()->getData($selfServiceGroups) : [];

                    // If we have single values we put it inside an array
                    $evaluatedUsers = is_array($evaluatedUsers) ? $evaluatedUsers : [$evaluatedUsers];
                    $evaluatedGroups = is_array($evaluatedGroups) ? $evaluatedGroups : [$evaluatedGroups];

                    $token->self_service_groups = ['users' => $evaluatedUsers, 'groups' => $evaluatedGroups];
                    break;
                case 'rule_expression':
                    $assignees = $token->process->getAssigneesFromExpressionRules($activity, $token);
                    $token->self_service_groups = ['users' => $assignees['users'], 'groups' => $assignees['groups']];
                    break;
                default:
                    $assignmentType = $activity->getProperty('assignment');
                    throw new \Exception("The Assignment Type '$assignmentType' is not compatible with the Self Service Option");
            }
        }

        //Default 3 days of due date
        $due = $this->getDueVariable($activity, $token);
        $token->due_at = $due ? Carbon::now()->addHours($due) : null;
        $token->initiated_at = null;
        $token->riskchanges_at = $due ? Carbon::now()->addHours($due * 0.7) : null;
        $token->updateTokenProperties();
        $token->getInstance()->updateCatchEvents();
        $token->saveOrFail();
        $token->setId($token->getKey());

        // Check if is script or self service again to send the Action by email with the updated token
        if (!$isScriptOrServiceTask) {
            $this->validateAndSendActionByEmail($activity, $token, $user->email);
        }

        $request = $token->getInstance();
        $request->notifyProcessUpdated('ACTIVITY_ACTIVATED', $token);
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    /**
     * Validate email configuration and Send Email
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     * @param string $to
     * @param array $data
     *
     * @return void
     */
    private function validateAndSendActionByEmail(ActivityInterface $activity, TokenInterface $token, string $to)
    {
        try {
            $isActionsByEmail = $activity->getProperty('isActionsByEmail', false);
            if ($isActionsByEmail) {
                $configEmail = json_decode($activity->getProperty('configEmail'), true);
                if (!empty($configEmail)) {
                    $abeRequestToken = new ProcessAbeRequestToken();
                    $tokenAbe = $abeRequestToken->updateOrCreate([
                        'process_id' => $token->getInstance()->process->getKey(),
                        'process_request_id' => $token->process_request_id,
                        'process_request_token_id' => $token->getKey(),
                        'completed_screen_id' => $configEmail['screenCompleteRef'] ?? 0,
                    ]);
                    $data = $token->getInstance()->getDataStore()->getData();
                    $data['token_abe'] = $tokenAbe->uuid;
                    // Send Email
                    return (new TaskActionByEmail())->sendAbeEmail($configEmail, 'luciana.nunez@processmaker.com', $data);
                }
            }
        } catch (\Exception $e) {
            // Catch and log the error
            Log::error('Failed to validate and send action by email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get due Variable
     *
     * @param ActivityInterface $activity
     * @param TokenInterface $token
     *
     * @return integer
     */
    private function getDueVariable(ActivityInterface $activity, TokenInterface $token)
    {
        $isDueVariable = $activity->getProperty('isDueInVariable', false);
        $dueVariable = $activity->getProperty('dueInVariable');
        if ($isDueVariable && !empty($dueVariable)) {
            $instanceData= $token->getInstance()->getDataStore()->getData();
            $mustache = new Mustache_Engine();
            $mustacheDueVariable = $mustache->render($dueVariable, $instanceData);
            return is_numeric($mustacheDueVariable) ? $mustacheDueVariable : '72';
        }
        return $activity->getProperty('dueIn', '72');
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
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }

        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'TRIGGERED';
        $token->element_id = $startEvent->getId();
        $token->element_type = $startEvent instanceof StartEventInterface ? 'startEvent' : 'task';
        $token->element_name = $startEvent->getName();
        $token->process_id = $token->getInstance()->getProcess()->getOwnerDocument()->getModel()->getKey();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = empty(Auth::user()) ? null : Auth::user()->id;

        $token->data = $token->getInstance()->getDataStore()->getData();
        $token->due_at = null;
        $token->initiated_at = Carbon::now();
        $token->completed_at = Carbon::now();
        $token->riskchanges_at = null;
        $token->updateTokenProperties();
        $token->saveOrFail();
        $token->setId($token->getKey());
        $request = $token->getInstance();
        $request->notifyProcessUpdated('START_EVENT_TRIGGERED', $token);
    }

    private function assignTaskUser(ActivityInterface $activity, TokenInterface $token, Instance $instance)
    {
    }

    /**
     * Persists instance and token data when a token within an activity change to error state
     *
     * @param ActivityInterface $activity
     * @param TokenInterface|ProcessRequestToken $token
     *
     * @return mixed
     */
    public function persistActivityException(ActivityInterface $activity, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceError($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->element_type = $this->getActivityType($activity);
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->updateTokenProperties();
        $token->save();
        $token->setId($token->getKey());
        $request = $token->getInstance();
        $request->notifyProcessUpdated('ACTIVITY_EXCEPTION', $token);
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
        if ($token->getInstance()->status === 'ERROR') {
            $token->getInstance()->status = 'ACTIVE';
        }
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
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
        $token->updateTokenProperties();
        $token->save();
        $token->setId($token->getKey());
        $request = $token->getInstance();
        $request->notifyProcessUpdated('ACTIVITY_COMPLETED', $token);
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
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $activity->getId();
        $token->element_type = $this->getActivityType($activity);
        $token->element_name = $activity->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->data = $token->getInstance()->getDataStore()->getData();
        $token->updateTokenProperties();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $token->status = $token->getStatus();
        $token->element_id = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->updateTokenProperties();
        $token->saveOrFail();
        $token->setId($token->getKey());
        $token->getInstance()->updateCatchEvents();
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
    }

    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $intermediateCatchEvent->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->updateTokenProperties();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventMessageArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_id = $intermediateCatchEvent->getId();
        $token->element_type = 'event';
        $token->element_name = $intermediateCatchEvent->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->updateTokenProperties();
        $token->saveOrFail();
        $token->setId($token->getKey());
    }

    public function persistCatchEventMessageConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $intermediateCatchEvent->getId();
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->updateTokenProperties();
        $token->save();
        $token->setId($token->getKey());
    }

    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, Collection $consumedTokens)
    {
    }

    public function persistGatewayTokenArrives(GatewayInterface $gateway, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        if ($token->exists) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = $token->getStatus();
        $token->element_index = $token->getIndex();
        $token->element_id = $gateway->getId();
        $token->element_type = 'gateway';
        $token->element_name = $gateway->getName();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->user_id = null;
        $token->due_at = null;
        $token->initiated_at = null;
        $token->riskchanges_at = null;
        $token->updateTokenProperties();
        $token->saveOrFail();
        $token->setId($token->getKey());
    }

    public function persistGatewayTokenConsumed(GatewayInterface $gateway, TokenInterface $token)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        $this->instanceRepository->persistInstanceUpdated($token->getInstance());
        $token->status = 'CLOSED';
        $token->element_id = $gateway->getId();
        $token->process_id = $token->getInstance()->process_id;
        $token->process_request_id = $token->getInstance()->getKey();
        $token->completed_at = Carbon::now();
        $token->updateTokenProperties();
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
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
        // we register just end event throw events
        if ($event instanceof EndEvent) {
            $this->instanceRepository->persistInstanceUpdated($token->getInstance());
            $token->status = 'CLOSED';
            $token->element_id = $event->getId();
            $token->element_type = 'end_event';
            $token->element_name = $event->getName();
            $token->process_id = $token->getInstance()->process_id;
            $token->process_request_id = $token->getInstance()->getKey();
            $token->user_id = null;
            $token->due_at = null;
            $token->riskchanges_at = null;
            $token->completed_at = Carbon::now();
            $token->updateTokenProperties();
            $token->saveOrFail();
            $token->setId($token->getKey());
        }
    }

    public function persistThrowEventTokenPassed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    public function store(TokenInterface $token, $saveChildElements = false)
    {
        // Update Nayra properties to process request token model
        foreach ($token->getProperties() as $key => $value) {
            if (array_key_exists($key, $token->getAttributes())) {
                $token->{$key} = $value;
            }
        }

        $token->saveOrFail();

        return $this;
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
            $userData = $user->attributesToArray();
            unset($userData['remember_token']);
            $instance->getDataStore()->putData('_user', $userData);
        }
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
            $instance->getDataStore()->putData('_request', $instance->attributesToArray());
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
        if ($activity instanceof ScriptTaskInterface) {
            return 'scriptTask';
        }

        if ($activity instanceof ServiceTaskInterface) {
            return 'serviceTask';
        }

        if ($activity instanceof CallActivityInterface) {
            return 'callActivity';
        }

        if ($activity instanceof ActivityInterface) {
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
    public function persistCallActivityActivated(TokenInterface $token, ExecutionInstanceInterface $subprocess, $startId)
    {
        $process = $token->getInstance()->getProcess();
        if ($process->isNonPersistent()) {
            return;
        }
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
        $token->subprocess_start_event_id = $startId;
        $token->updateTokenProperties();
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
