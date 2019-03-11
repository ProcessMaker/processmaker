<?php

namespace ProcessMaker\Managers;

use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Nayra\Bpmn\Models\TimerEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;

class TaskSchedulerManager implements JobManagerInterface, EventBusInterface
{
    /**
     *
     * Register in the database any Timer Start Event of a process
     *
     * @param \ProcessMaker\Models\Process $process
     * @return void
     * @internal param string $script Path to the javascript to load
     */
    public function registerTimerEvents(Process $process)
    {
        ScheduledTask::where('process_id', $process->id)
            ->where('type', 'TIMER_START_EVENT')
            ->delete();

        foreach ($process->getStartEvents() as $startEvent) {
            if (key_exists('eventDefinitions', $startEvent) && $startEvent['eventDefinitions']->count() > 0) {
                foreach ($startEvent['eventDefinitions'] as $eventDefinition) {
                    // here we just register Timer Events
                    if (get_class($eventDefinition) !== TimerEventDefinition::class) {
                        continue;
                    }

                    $timeDate = $eventDefinition->getTimeDate();
                    $timeCycle = $eventDefinition->getTimeCycle();
                    $timeDuration = $eventDefinition->getTimeDuration();

                    $timeEventType = '';
                    $period = null;
                    $intervals = [];

                    if ($timeDate && empty($timeEventType)) {
                        $timeEventType = 'TimeDate';
                        $period = $eventDefinition->getTimeDate()->getBody();
                    }

                    if ($timeCycle && empty($timeEventType)) {
                        $timeEventType = 'TimeCycle';
                        $period = $eventDefinition->getTimeCycle()->getBody();
                        $intervals = explode('|', $period);
                    }

                    if ($timeDuration && empty($timeEventType)) {
                        $timeEventType = 'TimeDuration';
                        $period = $eventDefinition->getTimeDuration()->getBody();
                    }

                    $init = $period[0] === 'R' ? 0 : 1;
                    for ($i = $init; $i < count($intervals); $i++) {
                        $parts = $this->getIntervalParts($intervals[$i]);

                        $configuration = [
                            'type' => $timeEventType,
                            'interval' => $this->buildInterval($parts, $timeEventType),
                            'element_id' => $startEvent['id']
                        ];

                        $scheduledTask = new ScheduledTask();
                        $scheduledTask->process_id = $process->id;
                        $scheduledTask->configuration = json_encode($configuration);
                        $scheduledTask->type = 'TIMER_START_EVENT';
                        $scheduledTask->last_execution = (new \DateTime())
                            ->setTimezone(new DateTimeZone('UTC'))
                            ->format('Y-m-d H:i:s');
                        $scheduledTask->save();
                    }
                }
            }
        }
    }


    /**
     * Register in the database Intermediate events of a request
     *
     * @param ProcessRequest $request
     */
    public function registerIntermediateTimerEvents(ProcessRequest $request)
    {
        $process = $request->process;

        ScheduledTask::where('process_id', $process->id)
            ->where('type', 'INTERMEDIATE_TIMER_EVENT')
            ->where('process_request_id', $request->id)
            ->delete();

        foreach ($process->getIntermediateCatchEvents() as $timerEvent) {
            if (key_exists('eventDefinitions', $timerEvent) && $timerEvent['eventDefinitions']->count() > 0) {
                foreach ($timerEvent['eventDefinitions'] as $eventDefinition) {
                    // here we just register Timer Events
                    if (get_class($eventDefinition) !== TimerEventDefinition::class) {
                        continue;
                    }

                    $timeDate = $eventDefinition->getTimeDate();
                    $timeCycle = $eventDefinition->getTimeCycle();
                    $timeDuration = $eventDefinition->getTimeDuration();

                    $timeEventType = '';
                    $period = null;
                    $intervals = [];

                    if ($timeDate && empty($timeEventType)) {
                        $timeEventType = 'TimeDate';
                        $period = $eventDefinition->getTimeDate()->getBody();
                        $intervals = explode('|', $period);
                    }

                    if ($timeCycle && empty($timeEventType)) {
                        $timeEventType = 'TimeCycle';
                        $period = $eventDefinition->getTimeCycle()->getBody();
                        $intervals = explode('|', $period);
                    }

                    if ($timeDuration && empty($timeEventType)) {
                        $timeEventType = 'TimeDuration';
                        $period = $eventDefinition->getTimeDuration()->getBody();
                        $intervals = explode('|', $period);
                    }

                    $init = ($period[0] === 'R' || $timeDate || $timeDuration) ? 0 : 1;
                    for ($i = $init; $i < count($intervals); $i++) {
                        $parts = $this->getIntervalParts($intervals[$i]);
                        $configuration = [
                            'type' => $timeEventType,
                            'interval' => $this->buildInterval($parts, $timeEventType),
                            'element_id' => $timerEvent['id']
                        ];

                        $scheduledTask = new ScheduledTask();
                        $scheduledTask->process_id = $process->id;
                        $scheduledTask->process_request_id = $request->id;
                        $scheduledTask->configuration = json_encode($configuration);
                        $scheduledTask->type = 'INTERMEDIATE_TIMER_EVENT';
                        $scheduledTask->last_execution = (new \DateTime())
                            ->setTimezone(new DateTimeZone('UTC'))
                            ->format('Y-m-d H:i:s');
                        $scheduledTask->save();
                    }
                }
            }
        }
    }

    /**
     * Checks the schedule_tasks table to execute jobs
     *
     * @param Schedule $schedule
     */
    public function scheduleTasks(Schedule $schedule)
    {
        try {
            if (!Schema::hasTable('scheduled_tasks')) {
                return;
            }

            $tasks = ScheduledTask::all();

            $today = (new \DateTime())->setTimezone(new DateTimeZone('UTC'));
            foreach ($tasks as $task) {
                $config = json_decode($task->configuration);

                $lastExecution = new \DateTime($task->last_execution, new DateTimeZone('UTC'));
                $nextDate = $this->nextDate($lastExecution, $config)->setTimezone(new DateTimeZone('UTC')) ;

                // if no execution date exists we go to the next task
                if (empty($nextDate)) {
                    continue;
                }

                $schedule->call(function () use ($task, $config, $today) {
                    switch ($task->type) {
                        case 'TIMER_START_EVENT':
                            $this->executeTimerStartEvent($task, $config);
                            $task->last_execution = $today->format('Y-m-d H:i:s');
                            $task->save();
                            break;
                        case 'INTERMEDIATE_TIMER_EVENT':
                            $executed = $this->executeIntermediateTimerEvent($task, $config);
                            $task->last_execution = $today->format('Y-m-d H:i:s');
                            if ($executed) {
                                $task->save();
                            }
                            break;
                    }
                })->when(function () use ($nextDate, $today) {
                    return $nextDate->setTimezone(new DateTimeZone('UTC')) < $today->setTimezone(new DateTimeZone('UTC'));
                });
            }
        } catch (\PDOException $e) {
            Log::error('The connection to the database had problems');
        }
    }

    public function executeTimerStartEvent($task, $config)
    {
        // Get the event BPMN element
        $id = $task->process_id;
        if (!$id) {
            return;
        }

        $process = Process::find($id);

        // If a process is configured to pause timer start events we do nothing
        if ($process->pause_timer_start === 1) {
            return;
        }

        $definitions = $process->getDefinitions();
        if (!$definitions->findElementById($config->element_id)) {
            return;
        }
        $event = $definitions->getEvent($config->element_id);
        $data = [];

        //Trigger the start event
        $processRequest = WorkflowManager::triggerStartEvent($process, $event, $data);
    }

    public function executeIntermediateTimerEvent($task, $config)
    {
        //Get the event BPMN element
        $id = $task->process_id;
        if (!$id) {
            return;
        }

        $process = Process::find($id);
        $request = ProcessRequest::find($task->process_request_id);


        $definitions = $process->getDefinitions();
        if (!$definitions->findElementById($config->element_id)) {
            return;
        }

        $catch = $request->tokens()->where('element_id', $config->element_id)->first();

        $executed = false;
        if ($catch && $catch->status == 'ACTIVE') {
            WorkflowManager::completeCatchEvent($process, $request, $catch, []);
            $executed = true;
        }

        return $executed;
    }

    public function nextDate($currentDate, $intervalConfig, $firstOccurrenceDate = null)
    {
        $result = null;
        switch ($intervalConfig->type) {
            case 'TimeDate':
                $result = $this->nextDateForOneDate($currentDate, $intervalConfig->interval, $firstOccurrenceDate =
                    null);
                break;
            case 'TimeCycle':
                $result = $this->nextDateForCyclical($currentDate, $intervalConfig->interval, $firstOccurrenceDate =
                    null);
                break;
            case 'TimeDuration':
                $result = $this->nextDateForDuration($currentDate, $intervalConfig->interval, $firstOccurrenceDate =
                    null);
                break;
        }

        return $result;
    }

    public function nextDateForCyclical($currentDate, $nayraInterval, $firstOccurrenceDate = null)
    {
        $parts = $this->getIntervalParts($nayraInterval);
        $firstDate = $firstOccurrenceDate === null
            ? $parts['firstDate']
            : $firstOccurrenceDate->format('Y-m-d H:i:s:z');

        $cont = 1;
        $nextDate = new \DateTime($firstDate);
        $endDate = new \DateTime($parts['endDate']);

        $dateToWork = empty($currentDate) ? new \DateTime() : $currentDate;

        // if the interval's first date has passed, we calculate the next date of the interval
        if ((new \DateTime($firstDate)) < $dateToWork) {
            while ($cont < $parts['recurrences'] || $parts['repetitions'] === 'R') {

                if ($nextDate >= $endDate) {
                    return null;
                }

                if ($parts['period']) {
                    $nextDate->add($parts['period']->getDateInterval());
                }

                if ($nextDate >= $dateToWork) {
                    break;
                }
                $cont++;
            }
        }

        // if the number of occurrences is reached, no nextDate exists
        if ($cont == $parts['recurrences'] && $parts['repetitions'] !== 'R') {
            $nextDate = null;
        }

        return $nextDate;
    }

    public function nextDateForOneDate($currentDate, $nayraInterval, $firstOccurrenceDate = null)
    {
        $parts = $this->getIntervalParts($nayraInterval);
        $firstDate = $firstOccurrenceDate === null
            ? $parts['firstDate']
            : $firstOccurrenceDate->format('Y-m-d H:i:s:z');

        $diff = date_diff(new \DateTime($firstDate), $currentDate);
        if ($diff->days * 24 * 60 + $diff->h * 60 + $diff->i < 2) {
            return new \DateTime($firstDate);
        }

        return null;
    }

    public function nextDateForDuration($currentDate, $nayraInterval, $firstOccurrenceDate = null)
    {
        $parts = $this->getIntervalParts($nayraInterval);
        return $currentDate->add($parts['period']);
    }

    private function getIntervalParts($nayraInterval)
    {
        $result = [];
        $parts = explode('/', $nayraInterval);

        if (count($parts) === 1 && $nayraInterval[0] === 'P') {
            $result['repetitions'] = '1';
            $result['interval'] = $parts[0];
            $result['firstDate'] = null;
            $result['period'] = new \DateInterval($nayraInterval);
            $result['recurrences'] = 1;
            return $result;
        }

        $result['endDate'] = (count($parts) === 4)
            ? (new \DateTime($parts[3]))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s') . 'Z'
            : (new \DateTime())
                ->add(new \DateInterval('P10Y'))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s') . 'Z';

        //if it is a specific date
        if (count($parts) === 1) {
            $result['repetitions'] = '1';
            $result['interval'] = null;
            $result['firstDate'] = (new \DateTime($parts[0]))
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d\TH:i:s') . 'Z';
            $result['period'] = null;
            $result['recurrences'] = 2;
            return $result;
        }

        $result['repetitions'] = $parts[0];
        $result['interval'] = $parts[2];

        $date = new \DateTime($parts[1]);
        $result['firstDate'] = $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s') . 'Z';

        if ($result['repetitions'] === 'R') {
            $result['period'] = new \DatePeriod('R1000000/' . $result['firstDate'] . '/' . $result['interval']);
        } else {
            $result['period'] = new \DatePeriod($result['repetitions'] . '/' . $result['firstDate'] . '/' . $result['interval']);
        }

        $result['recurrences'] = $result['period']->recurrences;
        return $result;
    }

    private function buildInterval($parts, $timeEventType)
    {
        $result = null;
        switch ($timeEventType) {
            case 'TimeDate':
                $result = $parts['firstDate'];
                break;
            case 'TimeCycle':
                $result = $parts['repetitions'] . '/' . $parts['firstDate'] . '/' . $parts['interval'] . '/' . $parts['endDate'];
                break;
            case 'TimeDuration':
                $result = $parts['interval'];
                break;
        }
        return $result;
    }

    /**
     * Schedule a job for a specific date and time for the given BPMN element,
     * event definition and an optional Token object
     *
     * @param string $datetime in ISO-8601 format
     * @param TimerEventDefinitionInterface $eventDefinition
     * @param EntityInterface $element
     * @param TokenInterface $token
     *
     * @return $this
     */
    public function scheduleDate($datetime, TimerEventDefinitionInterface $eventDefinition,
                                 FlowElementInterface $element, TokenInterface $token = null)
    {
    }

    /**
     * Schedule a job for a specific cycle for the given BPMN element, event definition
     * and an optional Token object
     *
     * @param string $cycle in ISO-8601 format
     * @param TimerEventDefinitionInterface $eventDefinition
     * @param EntityInterface $element
     * @param TokenInterface $token
     */
    public function scheduleCycle($cycle, TimerEventDefinitionInterface $eventDefinition, FlowElementInterface $element,
                                  TokenInterface $token = null)
    {
    }

    /**
     * Schedule a job execution after a time duration for the given BPMN element,
     * event definition and an optional Token object
     *
     * @param string $duration in ISO-8601 format
     * @param TimerEventDefinitionInterface $eventDefinition
     * @param EntityInterface $element
     * @param TokenInterface $token
     */
    public function scheduleDuration($duration, TimerEventDefinitionInterface $eventDefinition,
                                     FlowElementInterface $element, TokenInterface $token = null)
    {
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array $events
     * @param  mixed $listener
     *
     * @return void
     */
    public function listen($events, $listener)
    {
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param  string $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName)
    {
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  object|string $subscriber
     *
     * @return void
     */
    public function subscribe($subscriber)
    {
    }

    /**
     * Dispatch an event until the first non-null response is returned.
     *
     * @param  string|object $event
     * @param  mixed $payload
     *
     * @return array|null
     */
    public function until($event, $payload = [])
    {
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * @param  string|object $event
     * @param  mixed $payload
     * @param  bool $halt
     *
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param  string $event
     * @param  array $payload
     *
     * @return void
     */
    public function push($event, $payload = [])
    {
    }

    /**
     * Flush a set of pushed events.
     *
     * @param  string $event
     *
     * @return void
     */
    public function flush($event)
    {
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param  string $event
     *
     * @return void
     */
    public function forget($event)
    {
    }

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
    }
}