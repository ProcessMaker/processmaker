<?php

namespace ProcessMaker\Nayra\MessageBrokers;

use Exception;

class Service
{
    /**
     * Get service instance according to message broker driver
     *
     * @return $service
     */
    public static function create()
    {
        $type = config('app.message_broker_driver');

        switch ($type) {
            case 'rabbitmq':
                $service = new ServiceRabbitMq();
                break;
            case 'kafka':
                $service = new ServiceKafka();
                break;
            case 'default':
                $service = new ServiceKafka();
                echo "\033[0;31m" . 'The default driver does not use this consumer. MESSAGE_BROKER_DRIVER=' . config('app.message_broker_driver') . "\033[0m" . PHP_EOL;
                sleep(10);
                throw new Exception('This action requires a message broker configured and enabled in the configuration.');
            default:
                echo "\033[0;31m" . 'Unknown driver MESSAGE_BROKER_DRIVER=' . config('app.message_broker_driver') . "\033[0m" . PHP_EOL;
                sleep(10);
                throw new Exception('This action requires a message broker configured and enabled in the configuration.');
        }

        return $service;
    }
}
