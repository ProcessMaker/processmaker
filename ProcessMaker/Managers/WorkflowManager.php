<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Contracts\WorkflowManagerInterface;

class WorkflowManager
{
    /**
     * Get Workflow Manager according to message broker driver
     *
     * @return $workflowManager \ProcessMaker\Contracts\WorkflowManagerInterface
     */
    public static function create(): WorkflowManagerInterface
    {
        $type = env('MESSAGE_BROKER_DRIVER');

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
