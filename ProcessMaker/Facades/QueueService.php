<?php

namespace ProcessMaker\Facades;

use Illuminate\Support\Facades\Facade;
use ProcessMaker\Nayra\QueueService\QueueServiceInterface;

/**
 * @method static void config()
 * @method static void sendMessage(string $subject, string $collaborationId, mixed $body)
 * @method static string receiveMessage(string $queueName)
 */
class QueueService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return QueueServiceInterface::class;
    }
}
