<?php

namespace ProcessMaker\Nayra\Managers;

use ProcessMaker\Contracts\WorkflowManagerInterface;

class WorkflowManagerKafka extends WorkflowManagerRabbitMq implements WorkflowManagerInterface
{
    public function __construct()
    {
        // add prefix to the topic names
        $prefix = config('kafka.prefix');
        $this->TOPIC_SCRIPTS = $prefix . $this->TOPIC_SCRIPTS;
        $this->TOPIC_REQUESTS = $prefix . $this->TOPIC_REQUESTS;
    }
}
