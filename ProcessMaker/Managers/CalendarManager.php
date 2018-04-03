<?php

namespace ProcessMaker\Managers;


use ProcessMaker\Model\CalendarAssignment;
use ProcessMaker\Model\CalendarDefinition;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;

class CalendarManager
{
    const CALENDAR_UID_DEFAULT = '00000000000000000000000000000001';

    public function getCalendarDefinition(CalendarDefinition $calendar, $default = false)
    {
        $definition = CalendarDefinition::where('CALENDAR_UID', $calendar->CALENDAR_UID)->get();
        if ($default && empty($definition->toArray())) {

        }

    }

    public function getCalendarInformation(CalendarDefinition $calendar, $validate = false)
    {


    }

    public function getCalendarAssignment(User $user, Process $process, Task $task, $validate= true)
    {
        $assignment = CalendarAssignment::where('OBJECT_UID', [$user, $process, $task])->toArray();
        $calendarDefinition = new CalendarDefinition();
        $calendarDefinition->CALENDAR_UID = self::CALENDAR_UID_DEFAULT;
        $owner = 'DEFAULT';
        if ($assignment) {
            $last = end($assignment);
            switch ($last['OBJECT_UID']){
                case $user->USR_UID:
                    $calendarDefinition->CALENDAR_UID = $last['OBJECT_UID'];
                    $owner = 'USER';
                    break;
                case $process->PRO_UID:
                    $calendarDefinition->CALENDAR_UID = $last['OBJECT_UID'];
                    $owner = 'PROCESS';
                    break;
                case $task->TAS_UID:
                    $calendarDefinition->CALENDAR_UID = $last['OBJECT_UID'];
                    $owner = 'TASK';
                    break;
            }
        }
        $definition = $this->getCalendarInformation($calendarDefinition, $validate);
        $definition->owner = $owner;
        return $definition;
    }
}