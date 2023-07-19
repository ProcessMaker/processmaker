<?php

namespace ProcessMaker\Nayra\Repositories;

use ProcessMaker\Managers\TaskSchedulerManager;
use ProcessMaker\Repositories\ExecutionInstanceRepository;

trait PersistenceTimerEventsTrait
{
    protected Deserializer $deserializer;

    protected ExecutionInstanceRepository $instanceRepository;

    protected TaskSchedulerManager $taskSchedulerManager;

    public function persistScheduleDate(array $transaction)
    {
        $dateTime = unserialize($transaction['date_time']);
        $eventDefinition = $this->deserializer->unserializeEventDefinition($transaction['event_definition']);
        $element = $this->deserializer->unserializeEntity($transaction['element']);
        $token = $transaction['token'] ? $this->deserializer->unserializeToken($transaction['token']) : null;
        $this->taskSchedulerManager->scheduleDate($dateTime, $eventDefinition, $element, $token);
    }

    public function persistScheduleCycle(array $transaction)
    {
        $cycle = unserialize($transaction['cycle']);
        $eventDefinition = $this->deserializer->unserializeEventDefinition($transaction['event_definition']);
        $element = $this->deserializer->unserializeEntity($transaction['element']);
        $token = $transaction['token'] ? $this->deserializer->unserializeToken($transaction['token']) : null;
        $this->taskSchedulerManager->scheduleCycle($cycle, $eventDefinition, $element, $token);
    }

    public function persistScheduleDuration(array $transaction)
    {
        $duration = unserialize($transaction['duration']);
        $eventDefinition = $this->deserializer->unserializeEventDefinition($transaction['event_definition']);
        $element = $this->deserializer->unserializeEntity($transaction['element']);
        $token = $transaction['token'] ? $this->deserializer->unserializeToken($transaction['token']) : null;
        $this->taskSchedulerManager->scheduleDuration($duration, $eventDefinition, $element, $token);
    }
}
