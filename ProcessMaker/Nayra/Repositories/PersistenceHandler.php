<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

class PersistenceHandler
{
    use PersistenceRequestTrait;
    use PersistenceTokenTrait;

    protected Deserializer $deserializer;

    protected ExecutionInstanceRepository $instanceRepository;

    protected TokenRepository $tokenRepository;

    /**
     * PersistenceHandler constructor
     */
    public function __construct()
    {
        $this->deserializer = new Deserializer();
        $this->instanceRepository = new ExecutionInstanceRepository();
        $this->tokenRepository = new TokenRepository($this->instanceRepository);
    }

    /**
     * Save data
     *
     * @param array $transaction
     *
     * @throws Exception
     */
    public function save(array $transaction)
    {
        // Initialize session
        if (isset($transaction['session']) && !empty($transaction['session']['user_id']) && Auth::id() !== $transaction['session']['user_id']) {
            Auth::loginUsingId($transaction['session']['user_id']);
        }

        // Save data according to the type
        switch ($transaction['type']) {
            case 'activity_activated':
                $this->persistActivityActivated($transaction);
                break;
            case 'activity_exception':
                $this->persistActivityException($transaction);
                break;
            case 'activity_completed':
                $this->persistActivityCompleted($transaction);
                break;
            case 'activity_closed':
                $this->persistActivityClosed($transaction);
                break;
            case 'throw_event_token_arrives':
                $this->persistThrowEventTokenArrives($transaction);
                break;
            case 'throw_event_token_consumed':
                $this->persistThrowEventTokenConsumed($transaction);
                break;
            case 'throw_event_token_passed':
                $this->persistThrowEventTokenPassed($transaction);
                break;
            case 'gateway_token_arrives':
                $this->persistGatewayTokenArrives($transaction);
                break;
            case 'gateway_token_consumed':
                $this->persistGatewayTokenConsumed($transaction);
                break;
            case 'gateway_token_passed':
                $this->persistGatewayTokenPassed($transaction);
                break;
            case 'catch_event_token_arrives':
                $this->persistCatchEventTokenArrives($transaction);
                break;
            case 'catch_event_token_consumed':
                $this->persistCatchEventTokenConsumed($transaction);
                break;
            case 'catch_event_token_passed':
                $this->persistCatchEventTokenPassed($transaction);
                break;
            case 'catch_event_message_arrives':
                $this->persistCatchEventMessageArrives($transaction);
                break;
            case 'catch_event_message_consumed':
                $this->persistCatchEventMessageConsumed($transaction);
                break;
            case 'start_event_triggered':
                $this->persistStartEventTriggered($transaction);
                break;
            case 'event_based_gateway_activated':
                $this->persistEventBasedGatewayActivated($transaction);
                break;
            case 'instance_created':
                $this->persistInstanceCreated($transaction);
                break;
            case 'instance_completed':
                $this->persistInstanceCompleted($transaction);
                break;
            case 'instance_collaboration':
                $this->persistInstanceCollaboration($transaction);
                break;
            case 'instance_updated':
                $this->persistInstanceUpdated($transaction);
                break;
            default:
                throw new Exception('Unknown transaction type ' . $transaction['type']);
        }
    }
}
