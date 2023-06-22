<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\WorkflowManagerInterface;
use ProcessMaker\Nayra\Managers\WorkflowManagerDefault;
use ProcessMaker\Nayra\Managers\WorkflowManagerKafka;
use ProcessMaker\Nayra\Managers\WorkflowManagerRabbitMq;

class WorkflowManager
{
    /**
     * Get Workflow Manager according to message broker driver
     *
     * @return $workflowManager \ProcessMaker\Contracts\WorkflowManagerInterface
     */
    public static function create(): WorkflowManagerInterface
    {
        $type = config('app.message_broker_driver');

        switch ($type) {
            case 'rabbitmq':
                $workflowManager = new WorkflowManagerRabbitMq();
                break;
            case 'kafka':
                $workflowManager = new WorkflowManagerKafka();
                break;
            default:
                $workflowManager = new WorkflowManagerDefault();
                break;
        }

        return $workflowManager;
    }
}
