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
            default:
                throw new Exception('This action requires a message broker configured and enabled in the configuration.');
        }

        return $service;
	}
}
