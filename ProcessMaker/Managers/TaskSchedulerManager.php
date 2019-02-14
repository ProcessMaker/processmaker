<?php
namespace ProcessMaker\Managers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Facades\WorkflowManager;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ScheduledTask;
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
        ScheduledTask::where('process_id', $process->id)->delete();

        foreach ($process->getStartEvents() as $startEvent) {
            if (key_exists('eventDefinitions', $startEvent) && count($startEvent) > 0) {
                $eventDefinitions = $startEvent['eventDefinitions']->item(0)->getTimeCycle()->getBody();
                foreach($startEvent['eventDefinitions'] as $eventDefinition) {
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

                    for ($i = 1; $i < count($intervals); $i++) {
                        $configuration = [
                            'type' => $timeEventType,
                            'interval' => $intervals[$i]
                        ];

                        $scheduledTask = new ScheduledTask();
                        $scheduledTask->process_id = $process->id;
                        $scheduledTask->configuration = json_encode($configuration);
                        $scheduledTask->type = 'TIMER_START_EVENT';
                        $scheduledTask->save();
                    }
                }
            }
        }
    }

   public function scheduleTasks(Schedule $schedule)
   {
       if (!Schema::hasTable('scheduled_task')) {
           return;
       }
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
       $parts = $this->getIntervalParts($nayraInterval);
       $firstDate = $firstOccurrenceDate === null
                    ? $parts['firstDate']
                    : $firstOccurrenceDate->format('Y-m-d H:i:s:z');

       $cont = 1;
       $nextDate = new \DateTime($firstDate);
       while ($cont < $parts['recurrences'] || $parts['repetitions'] === 'R') {
           $nextDate->add($parts['period']->getDateInterval());
           if ($nextDate >= $currentDate) {
               break;
           }
           $cont ++;
       }

       return $nextDate;
   }

   private function getIntervalParts($nayraInterval)
   {
       $result = [];
       $parts = explode('/', $nayraInterval);

       $result['repetitions'] = $parts[0];
       $result['firstDate'] = $parts[1];
       $result['interval'] = $parts[2];

       if ($result['repetitions'] === 'R') {
           $result['period'] = new \DatePeriod('R1000000/'. $result['firstDate'] . '/' . $result['interval']);
       }
       else {
           $result['period'] = new \DatePeriod($result['repetitions']. '/'. $result['firstDate'] . '/' . $result['interval']);
       }

       $result['recurrences'] = $result['period']->recurrences;
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