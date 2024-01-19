<?php

namespace ProcessMaker\Nayra\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Listeners\BpmnSubscriber;
use ProcessMaker\Listeners\CommentsSubscriber;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Repositories\TokenRepository;

trait PersistenceTokenTrait
{
    protected TokenRepository $tokenRepository;

    private static $aboutCacheKey = 'nayra.about';

    /**
     * Persists instance and token data when a token arrives to an activity
     *
     * @param array $transaction
     */
    public function persistActivityActivated(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityActivated($activity, $token);

        // Event
        $event = new ActivityActivatedEvent($activity, $token);
        app('events')->dispatch(ActivityInterface::EVENT_ACTIVITY_ACTIVATED, $event);
    }

    /**
     * Persists instance and token data when a token within an activity change to error state
     *
     * @param array $transaction
     */
    public function persistActivityException(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityException($activity, $token);

        // Event
        $bpmnSubscriber = new BpmnSubscriber();
        $bpmnSubscriber->onActivityException($activity, $token);
    }

    /**
     * Persists instance and token data when a token is completed within an activity
     *
     * @param array $transaction
     */
    public function persistActivityCompleted(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityCompleted($activity, $token);

        // Event
        $event = new ActivityCompletedEvent($activity, $token);
        app('events')->dispatch(ActivityInterface::EVENT_ACTIVITY_COMPLETED, $event);
    }

    /**
     * Persists instance and token data when a token is closed by an activity
     *
     * @param array $transaction
     */
    public function persistActivityClosed(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityClosed($activity, $token);

        // Event
        $bpmnSubscriber = new BpmnSubscriber();
        $event = new ActivityClosedEvent($activity, $token);
        $bpmnSubscriber->onActivityClosed($event);
    }

    public function persistActivitySkipped(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);

        // Comments
        $subscriber = new CommentsSubscriber();
        $subscriber->onActivitySkipped($activity, $token);
    }

    /**
     * Persists instance and token data when a throw event token arrives
     *
     * @param array $transaction
     */
    public function persistThrowEventTokenArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenArrives($event, $token);
    }

    /**
     * Persists instance and token data when a throw event token is consumed
     *
     * @param array $transaction
     */
    public function persistThrowEventTokenConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenConsumed($event, $token);
    }

    /**
     * Persists instance and token data when a throw event token is passed
     *
     * @param array $transaction
     */
    public function persistThrowEventTokenPassed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenPassed($event, $token);
    }

    /**
     * Persists instance and token data when a gateway token arrives
     *
     * @param array $transaction
     */
    public function persistGatewayTokenArrives(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistGatewayTokenArrives($gateway, $token);
    }

    /**
     * Persists instance and token data when a gateway token is consumed
     *
     * @param array $transaction
     */
    public function persistGatewayTokenConsumed(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistGatewayTokenConsumed($gateway, $token);
    }

    /**
     * Persists instance and token data when a gateway token is passed
     *
     * @param array $transaction
     */
    public function persistGatewayTokenPassed(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        if (!is_numeric($transaction['transition'])) {
            Log::info('Invalid transition id for gateway token passed. ' . json_encode($transaction));

            return;
        }
        $transition = $gateway->getTransitions()[$transaction['transition']] ?? null;
        if (empty($transition)) {
            Log::info('Invalid transition for gateway token passed. ' . json_encode($transaction));

            return;
        }
        $tokens = $this->deserializer->unserializeTokensCollection($transaction['tokens']);
        $this->tokenRepository->persistGatewayTokenPassed($gateway, $tokens->item(0));

        // Comments
        $subscriber = new CommentsSubscriber();
        $subscriber->onGatewayPassed($gateway, $transition, $tokens);
    }

    /**
     * Persists instance and token data when a catch event token arrives
     *
     * @param array $transaction
     */
    public function persistCatchEventTokenArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventTokenArrives($event, $token);

        // Event
        $bpmnSubscriber = new BpmnSubscriber();
        $bpmnSubscriber->onIntermediateCatchEventActivated($event, $token);
    }

    /**
     * Persists instance and token data when a catch event token is consumed
     *
     * @param array $transaction
     */
    public function persistCatchEventTokenConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventTokenConsumed($event, $token);
    }

    /**
     * Persists instance and token data when a catch event token is passed
     *
     * @param array $transaction
     */
    public function persistCatchEventTokenPassed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistCatchEventTokenPassed($event, $consumedTokens);
    }

    /**
     * Persists instance and token data when a catch event message arrives
     *
     * @param array $transaction
     */
    public function persistCatchEventMessageArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventMessageArrives($event, $token);
    }

    /**
     * Persists instance and token data when a catch event message is consumed
     *
     * @param array $transaction
     */
    public function persistCatchEventMessageConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventMessageConsumed($event, $token);
    }

    /**
     * Persists tokens that triggered a Start Event
     *
     * @param array $transaction
     */
    public function persistStartEventTriggered(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['start_event']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistStartEventTriggered($event, $consumedTokens);
    }

    /**
     * Persists instance and token data when a token is consumed in a event based gateway
     *
     * @param array $transaction
     */
    public function persistEventBasedGatewayActivated(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $passedToken = $this->deserializer->unserializeToken($transaction['passed_token']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistEventBasedGatewayActivated($gateway, $passedToken, $consumedTokens);
    }

    /**
     * Persists a Call Activity Activated
     *
     * @param array $transaction
     */
    public function persistCallActivityActivated(array $transaction)
    {
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $subprocessInstance = $this->deserializer->unserializeInstance($transaction['subprocess']);
        $startId = $transaction['start_id'];
        $this->tokenRepository->persistCallActivityActivated($token, $subprocessInstance, $startId);
    }

    /**
     * Store the about information into cache
     *
     * @param array $transaction
     * @return void
     */
    public function persistAbout(array $aboutInfo)
    {
        if (!array_key_exists('name', $aboutInfo) ||
            !array_key_exists('description', $aboutInfo) ||
            !array_key_exists('version', $aboutInfo)
        ) {
            error_log('Invalid about message received. ' . json_encode($aboutInfo));

            return;
        }

        $name = $aboutInfo['name'];
        $version = $aboutInfo['version'];
        error_log("Microservice $name version $version is running.");
        Cache::put(self::$aboutCacheKey, $aboutInfo, 60);
    }

    public function throwGlobalSignalEvent(array $transaction)
    {
        $throwElement = $this->deserializer->unserializeEntity($transaction['throw_element']);
        $token = $transaction['token'] ? $this->deserializer->unserializeToken($transaction['token']) : null;
        $eventDefinition = $throwElement->getEventDefinitions()->item(0);
        WorkflowManager::throwSignalEventDefinition($eventDefinition, $token);
    }
}
