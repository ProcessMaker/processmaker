<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Nayra\MessageBrokers\ServiceInterface;

/**
 * @see \ProcessMaker\Nayra\MessageBrokers\ServiceKafka
 * @see \ProcessMaker\Nayra\MessageBrokers\ServiceRabbitMq
 * 
 * @method static void connect()
 * @method static void disconnect()
 * @method static void sendMessage(string $subject, string $collaborationId, mixed $body)
 * @method static string receiveMessage(string $queueName)
 * @method static void worker()
 */
class MessageBrokerService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ServiceInterface::class;
    }
}
