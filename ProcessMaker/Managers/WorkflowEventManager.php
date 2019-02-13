<?php
namespace ProcessMaker\Managers;

use Illuminate\Console\Scheduling\Schedule;
use ProcessMaker\Models\ScheduledTask;

class WorkflowEventManager
{
//    public function __construct()
//    {
//    }

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

   public function checkScheduledTasks(Schedule $schedule)
   {
       $tasks = ScheduledTask::all();

       foreach($tasks as $task) {
           $schedule->call(function() use($task) {

           })->at();
       }
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
}