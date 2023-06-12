<?php

namespace ProcessMaker\Nayra\Repositories;

use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

trait PersistanceTokenTrait
{
    protected Deserializer $deserializer;

    protected ExecutionInstanceRepository $instanceRepository;

    protected TokenRepository $tokenRepository;

    public function persistActivityActivated(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityActivated($activity, $token);
    }

    public function persistActivityException(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityException($activity, $token);
    }

    public function persistActivityCompleted(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityCompleted($activity, $token);
    }

    public function persistActivityClosed(array $transaction)
    {
        $activity = $this->deserializer->unserializeEntity($transaction['activity']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistActivityClosed($activity, $token);
    }

    public function persistThrowEventTokenArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenArrives($event, $token);
    }

    public function persistThrowEventTokenConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenConsumed($event, $token);
    }

    public function persistThrowEventTokenPassed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistThrowEventTokenPassed($event, $token);
    }

    public function persistGatewayTokenArrives(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistGatewayTokenArrives($gateway, $token);
    }

    public function persistGatewayTokenConsumed(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistGatewayTokenConsumed($gateway, $token);
    }

    public function persistGatewayTokenPassed(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistGatewayTokenPassed($gateway, $token);
    }

    public function persistCatchEventTokenArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventTokenArrives($event, $token);
    }

    public function persistCatchEventTokenConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventTokenConsumed($event, $token);
    }

    public function persistCatchEventTokenPassed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistCatchEventTokenPassed($event, $consumedTokens);
    }

    public function persistCatchEventMessageArrives(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventMessageArrives($event, $token);
    }

    public function persistCatchEventMessageConsumed(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['catch_event']);
        $token = $this->deserializer->unserializeToken($transaction['token']);
        $this->tokenRepository->persistCatchEventMessageConsumed($event, $token);
    }

    public function persistStartEventTriggered(array $transaction)
    {
        $event = $this->deserializer->unserializeEntity($transaction['start_event']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistStartEventTriggered($event, $consumedTokens);
    }

    public function persistEventBasedGatewayActivated(array $transaction)
    {
        $gateway = $this->deserializer->unserializeEntity($transaction['gateway']);
        $passedToken = $this->deserializer->unserializeToken($transaction['passed_token']);
        $consumedTokens = $this->deserializer->unserializeTokensCollection($transaction['consumed_tokens']);
        $this->tokenRepository->persistEventBasedGatewayActivated($gateway, $passedToken, $consumedTokens);
    }
}
