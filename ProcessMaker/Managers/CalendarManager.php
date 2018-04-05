<?php

namespace ProcessMaker\Managers;

use ProcessMaker\Exception\CalendarInformationException;
use ProcessMaker\Model\CalendarAssignment;
use ProcessMaker\Model\CalendarBusinessHorus;
use ProcessMaker\Model\CalendarDefinition;
use ProcessMaker\Model\CalendarHolidays;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;
use stdClass;

class CalendarManager
{
    const CALENDAR_UID_DEFAULT = '00000000000000000000000000000001';

    /**
     * Load calendar of default
     *
     * @return CalendarDefinition
     */
    private function getCalendarDefault()
    {
        $calendarDefault = new CalendarDefinition(['CALENDAR_UID' => self::CALENDAR_UID_DEFAULT]);
        $definition = $this->getCalendarDefinition($calendarDefault, false);
        if (empty($definition->toArray())) {
            $this->saveCalendarInformation([
                'CALENDAR_UID' => '00000000000000000000000000000001',
                'CALENDAR_NAME' => G::LoadTranslation('ID_DEFAULT_CALENDAR'),
                'CALENDAR_CREATE_DATE' => date( 'Y-m-d' ),
                'CALENDAR_UPDATE_DATE' => date( 'Y-m-d' ),
                'CALENDAR_DESCRIPTION' => G::LoadTranslation('ID_DEFAULT_CALENDAR'),
                'CALENDAR_STATUS' => 'ACTIVE',
                'CALENDAR_WORK_DAYS' => '1|2|3|4|5',
                'BUSINESS_DAY' => ['1' => [
                    'CALENDAR_BUSINESS_DAY' => 7,
                    'CALENDAR_BUSINESS_START' => '09:00',
                    'CALENDAR_BUSINESS_END' => '17:00',
                ]],
                'HOLIDAY' => []
            ]);
            $definition = $this->getCalendarDefault();
        }
        return $definition;
    }

    /**
     * Get Calendar definition
     *
     * @param CalendarDefinition $calendar
     * @param bool $default If the calendar does not exist, return to the default calendar.
     *
     * @return CalendarDefinition
     */
    public function getCalendarDefinition(CalendarDefinition $calendar, $default = false)
    {
        $definition = CalendarDefinition::where('CALENDAR_UID', $calendar->CALENDAR_UID)->get();
        if ($default && empty($definition->toArray())) {
            $definition = $this->getCalendarDefault();
        }
        return $definition;
    }

    /**
     * Get Calendar information (Definition, holidays, business hours)
     *
     * @param CalendarDefinition $calendar
     * @param bool $validate
     *
     * @return stdClass
     */
    public function getCalendarInformation(CalendarDefinition $calendar, $validate = false)
    {
        $definition = $this->getCalendarDefinition($calendar, true);
        $definition->BUSINESS_DAY = $definition->businessHorus();
        $definition->HOLIDAY = $definition->holidays();
        if ($validate) {
            $definition = $this->validateCalendarInformation($definition->toArray());
        }
        return $definition;
    }

    /**
     * Validate information of calendar
     *
     * @param array $data
     * @return stdClass
     */
    public function validateCalendarInformation($data)
    {
        try {
            //Validate if Working days are Correct, by default minimum 3
            $workingDays = explode('|', $data['CALENDAR_WORK_DAYS']);
            if (count($workingDays) < 3) {
                throw new CalendarInformationException('You must define at least 3 Working Days!');
            }
            //Validate that all Working Days have Business Hours
            if (count($data['BUSINESS_DAY']) < 1) {
                throw new CalendarInformationException('You must define at least one Business Day for all days');
            }
            $workingDaysOK = [];
            foreach ($workingDays as $day) {
                $workingDaysOK[$day] = false;
            }
            $sw_all = false;
            foreach ($data['BUSINESS_DAY'] as $businessHours) {
                if ($businessHours['CALENDAR_BUSINESS_DAY'] === 7) {
                    $sw_all = true;
                } elseif (in_array($businessHours['CALENDAR_BUSINESS_DAY'], $workingDays)) {
                    $workingDaysOK[$businessHours['CALENDAR_BUSINESS_DAY']] = true;
                }
            }
            $sw_days = true;

            foreach ($workingDaysOK as $sw_day) {
                $sw_days = $sw_days && $sw_day;
            }
            if (!($sw_all || $sw_days)) {
                throw new CalendarInformationException('Not all working days have their correspondent business day');
            }
            return (object)$data;
        } catch (CalendarInformationException $e) {
            $calendar = new CalendarDefinition(['CALENDAR_UID' => self::CALENDAR_UID_DEFAULT]);
            return $this->getCalendarInformation($calendar);
        }
    }

    /**
     * Get calendar assignment by user, process or task
     *
     * @param User $user
     * @param Process $process
     * @param Task $task
     * @param bool $validate If information validation is required
     *
     * @return stdClass
     */
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

    /**
     * Save all Information of calendar
     *
     * @param array $information
     * @throws \Throwable
     */
    public function saveCalendarInformation($information)
    {
        $definition = $this->saveCalendarDefinition($information);
        if (isset($definition['BUSINESS_DAY'] ) && is_array($definition['BUSINESS_DAY'])) {
            foreach ($definition['BUSINESS_DAY'] as $businessHours) {
                $businessHours['CALENDAR_UID'] = $definition->CALENDAR_UID;
                $businessHours['CALENDAR_ID'] = $definition->CALENDAR_ID;
                $this->saveCalendarBusinessHours($businessHours);
            }
        }
        if (isset($definition['HOLIDAY'] ) && is_array($definition['HOLIDAY'])) {
            foreach ($definition['HOLIDAY'] as $holiday) {
                $holiday['CALENDAR_UID'] = $definition->CALENDAR_UID;
                $holiday['CALENDAR_ID'] = $definition->CALENDAR_ID;
                $this->saveCalendarHolidays($holiday);
            }
        }
    }

    /**
     * Save Calendar Definition
     *
     * @param array $data
     *
     * @return CalendarDefinition
     * @throws \Throwable
     */
    public function saveCalendarDefinition($data) :CalendarDefinition
    {
        $definition = new CalendarDefinition();
        $data['CALENDAR_UID'] = str_replace('-', '', Uuid::uuid4());
        $definition->fill($data);
        $definition->saveOrFail();
        return $definition;
    }

    /**
     * Save Calendar Business Hours
     *
     * @param $data
     *
     * @return CalendarBusinessHorus
     * @throws \Throwable
     */
    public function saveCalendarBusinessHours($data)
    {
        $businessHours = new CalendarBusinessHorus();
        $businessHours->fill($data);
        $businessHours->saveOrFail();
        return $businessHours;

    }

    /**
     * Save Calendar Business Hours
     *
     * @param $data
     *
     * @return CalendarHolidays
     * @throws \Throwable
     */
    public function saveCalendarHolidays($data)
    {
        $holidays = new CalendarHolidays();
        $holidays->fill($data);
        $holidays->saveOrFail();
        return $holidays;
    }
}