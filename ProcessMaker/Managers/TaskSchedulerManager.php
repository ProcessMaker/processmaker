<?php
namespace ProcessMaker\Managers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;
use ProcessMaker\Facades\WorkflowManager;
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
     * Register in the database any Start Event that a process has
     *
     * @param \ProcessMaker\Models\Process $process
     * @return void
     * @internal param string $script Path to the javascript to load
     */
    public function registerTimerEvents(\ProcessMaker\Models\Process $process)
    {
        foreach ($process->getStartEvents() as $startEvent) {
            if (key_exists('eventDefinitions', $startEvent) && count($startEvent) > 0) {
                $eventDefinitions = $startEvent['eventDefinitions']->item(0)->getTimeCycle()->getBody();
                foreach($startEvent['eventDefinitions'] as $eventDefinition) {
                    $timeDate = $eventDefinition->getTimeDate();
                    $timeCycle = $eventDefinition->getTimeCycle();
                    $timeDuration = $eventDefinition->getTimeDuration();

                    $timeEventType = '';
                    $period = null;

                    if ($timeDate && empty($timeEventType)) {
                        $timeEventType = 'TimeDate';
                        $period = $eventDefinition->getTimeDate()->getBody();
                    }

                    if ($timeCycle && empty($timeEventType)) {
                        $timeEventType = 'TimeCycle';
                        $period = $eventDefinition->getTimeCycle()->getBody();
                        $datePeriod = new \DatePeriod($period);
                        $interval = $datePeriod->getDateInterval();
                    }

                    if ($timeDuration && empty($timeEventType)) {
                        $timeEventType = 'TimeDuration';
                        $period = $eventDefinition->getTimeDuration()->getBody();
                    }

                    $configuration = [
                        'type' => $timeEventType,
                        'interval' => $period
                    ];

                    $scheduledTask = new ScheduledTask();
                    $scheduledTask->process_id = $process->id;
                    $scheduledTask->configuration = json_encode($configuration);
                    $scheduledTask->save();
                }
            }
        }
    }

   public function scheduleTasks(Schedule $schedule)
   {

       Log::info('Inicio de scheduleTasks');
       $tasks = ScheduledTask::all();

       foreach($tasks as $task) {
           $config = json_decode($task->configuration);
           $today = new \DateTime();
           $nextDate = $this->nextDate($today, $config->interval);

           Log::info($nextDate->format('Y-m-d H:i:s:z'));

           $schedule->call(function() use($task, $config) {
               switch ($task->type) {
                   case 'TIMER_START_EVENT':
                       $this->executeTimerStartEvent($task, $config);
                       break;
               }
           })->when(function() use($nextDate, $today) {
               return $nextDate < $today;
           });
       }
   }

   public function executeTimerStartEvent($task, $config)
   {
       Log::info('inicio de exectute timer start event');

       //Get the event BPMN element
       $id = $task->process_id;
       if (!$id) {
           Log::error('The process '. $id.' does not exist in the database');
           return;
       }

       Log::info('Proceso: '.$id);

       $process = Process::find($id);

       $definitions = $process->getDefinitions();
       if (!$definitions->findElementById($config->element_id)) {
           Log::error('The timer start event '. $config->event_id.' does not exist in the database');
           return;
       }
       $event = $definitions->getEvent($config->element_id);
       $data = [];

       $archivo = fopen('/tmp/archivo_'.time().'.txt', 'w');
       fwrite($archivo, 'Iniciando... '. $id);
       fclose($archivo);

       //Trigger the start event
       $processRequest = WorkflowManager::triggerStartEvent($process, $event, $data);
   }

   public function nextDate($currentDate, $nayraInterval, $firstOccurrenceDate = null)
   {
       $parts = explode('/', $nayraInterval);
       $repetitions = $parts[0];

       //if the user doesn't send a first day of occurrence, we'll use the one in the interval
       $firstDate = $firstOccurrenceDate === null
                    ? $parts[1]
                    : $firstOccurrenceDate->format('Y-m-d H:i:s:z');

       if ($firstOccurrenceDate === null) {
           $firstDate = $parts[1];
       }

       $interval = $parts[2];
       if ($repetitions === 'R') {
           $period = new \DatePeriod('R1000000/'. $firstDate . '/' . $interval);
       }
       else {
           $period = new \DatePeriod($repetitions. '/'. $firstDate . '/' . $interval);
       }

       $recurrences = $period->recurrences;
       $cont = 1;
       $nextDate = new \DateTime($firstDate);
       while ($cont < $recurrences || $repetitions === 'R') {
           $nextDate->add($period->getDateInterval());
           if ($nextDate >= $currentDate) {
               break;
           }
           $cont ++;
       }

       return $nextDate;
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