<?php

namespace Tests\unit\ProcessMaker\Managers;

use Faker\Factory as Faker;
use ProcessMaker\Exception\CalendarInformationException;
use ProcessMaker\Managers\CalendarManager;
use ProcessMaker\Model\CalendarDefinition;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Task;
use ProcessMaker\Model\User;
use Tests\TestCase;

class CalendarManagerTest extends TestCase
{
    /**
     * @var CalendarManager
     */
    private $calendar;

    /**
     * CalendarManagerTest constructor.
     * @param null|string $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->calendar = new CalendarManager();
    }

    /**
     * Test Load Calendar by Default
     */
    public function testGetCalendarDefault()
    {
        $calendar = CalendarDefinition::where('CALENDAR_UID', '00000000000000000000000000000001')->first();
        if ($calendar) {
            $calendar->delete();
        }
        $default = $this->calendar->getCalendarDefault()->toArray();

        //Verify structure
        $this->verifyStructure($default);
    }

    /**
     * Test Load Calendar definition
     */
    public function testGetCalendarDefinition()
    {
        $calendar = new CalendarDefinition(['CALENDAR_UID' => '00000000000000000000000000000001']);
        $default = $this->calendar->getCalendarDefinition($calendar, true)->toArray();

        //Verify structure
        $this->verifyStructure($default);
    }

    /**
     * Test Load All information of calendar
     */
    public function testGetCalendarInformation()
    {
        $calendar = new CalendarDefinition(['CALENDAR_UID' => '00000000000000000000000000000002']);
        $default = $this->calendar->getCalendarInformation($calendar, true)->toArray();

        //Verify structure
        $this->verifyStructure($default, true, true);
    }

    /**
     * Test Saver All information Calendar
     *
     * @return CalendarDefinition
     *
     */
    public function testSaveCalendarInformation()
    {
        $faker = Faker::create();
        $calendar = [
            'CALENDAR_NAME' => $faker->sentence(2, true),
            'CALENDAR_DESCRIPTION' => $faker->sentence(5, true),
            'CALENDAR_STATUS' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
            'CALENDAR_WORK_DAYS' => $faker->randomElement(['1|2|3|4|5', '1|3|5', '2|4|6']),
            'BUSINESS_DAY' => [
                [
                    'CALENDAR_BUSINESS_DAY' => $faker->randomElement([5, 6, 7]),
                    'CALENDAR_BUSINESS_START' => $faker->randomElement(['08:00', '09:00', '10:00']),
                    'CALENDAR_BUSINESS_END' => $faker->randomElement(['16:00', '17:00', '18:00'])
                ]
            ],
            'HOLIDAY' => [
                [
                    'CALENDAR_HOLIDAY_NAME' => $faker->word,
                    'CALENDAR_HOLIDAY_START' => $faker->date(),
                    'CALENDAR_HOLIDAY_END' => $faker->date()
                ]
            ]
        ];
        $response = $this->calendar->saveCalendarInformation($calendar);

        $data = $this->calendar->getCalendarInformation($response)->toArray();

        //Verify structure
        $this->verifyStructure($data, true, true);

        //Verify correctly calendar information
        $this->assertEquals($data['CALENDAR_NAME'], $calendar['CALENDAR_NAME']);
        $this->assertEquals($data['CALENDAR_DESCRIPTION'], $calendar['CALENDAR_DESCRIPTION']);
        $this->assertEquals($data['CALENDAR_STATUS'], $calendar['CALENDAR_STATUS']);
        $this->assertEquals($data['CALENDAR_WORK_DAYS'], $calendar['CALENDAR_WORK_DAYS']);
        $this->assertEquals($data['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_DAY'], $calendar['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_DAY']);
        $this->assertEquals($data['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_START'], $calendar['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_START']);
        $this->assertEquals($data['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_END'], $calendar['BUSINESS_DAY'][0]['CALENDAR_BUSINESS_END']);

        $this->assertEquals($data['HOLIDAY'][0]['CALENDAR_HOLIDAY_NAME'], $calendar['HOLIDAY'][0]['CALENDAR_HOLIDAY_NAME']);
        $this->assertEquals(date('Y-m-d', strtotime($data['HOLIDAY'][0]['CALENDAR_HOLIDAY_START'])), $calendar['HOLIDAY'][0]['CALENDAR_HOLIDAY_START']);
        $this->assertEquals(date('Y-m-d', strtotime($data['HOLIDAY'][0]['CALENDAR_HOLIDAY_END'])), $calendar['HOLIDAY'][0]['CALENDAR_HOLIDAY_END']);

        return $response;
    }

    /**
     * Test Calendar assignment by default
     */
    public function testGetAssignmentDefault()
    {
        $result = $this->calendar->getCalendarAssignment(new User(), new Process(), new Task())->toArray();

        $this->verifyStructure($result, true, true);
        //verify calendar owner
        $this->assertArrayHasKey('OWNER', $result);
        $this->assertEquals($result['OWNER'], 'DEFAULT');
    }

    /**
     * Test Calendar assignment by default
     *
     * @param CalendarDefinition $calendar
     * @depends testSaveCalendarInformation
     */
    public function testGetAssignmentUser(CalendarDefinition $calendar)
    {
        $user = factory(User::class)->create();

        $this->calendar->saveCalendarAssignment([
            'OBJECT_UID' => $user->USR_UID,
            'OBJECT_TYPE' => 'Calendar test',
            'CALENDAR_UID' => $calendar->CALENDAR_UID,
            'CALENDAR_ID' => $calendar->CALENDAR_ID,
        ]);

        $result = $this->calendar->getCalendarAssignment($user, new Process(), new Task())->toArray();

        $this->verifyStructure($result, true, true);
        //verify calendar owner
        $this->assertArrayHasKey('OWNER', $result);
        $this->assertEquals($result['OWNER'], 'USER');

        //$this->assertEquals($result['CALENDAR_UID'], $calendar->CALENDAR_UID);
    }

    /**
     * Test Calendar assignment by default
     *
     * @param CalendarDefinition $calendar
     * @depends testSaveCalendarInformation
     */
    public function testGetAssignmentProcess(CalendarDefinition $calendar)
    {
        $process = factory(Process::class)->create();

        $this->calendar->saveCalendarAssignment([
            'OBJECT_UID' => $process->PRO_UID,
            'OBJECT_TYPE' => 'Calendar test',
            'CALENDAR_UID' => $calendar->CALENDAR_UID,
            'CALENDAR_ID' => $calendar->CALENDAR_ID,
        ]);
        $user = new User();
        $user->USR_UID = '';

        $result = $this->calendar->getCalendarAssignment($user, $process, new Task())->toArray();

        $this->verifyStructure($result, true, true);
        //verify calendar owner
        $this->assertArrayHasKey('OWNER', $result);
        $this->assertEquals($result['OWNER'], 'PROCESS');
    }

    /**
     * Test Calendar assignment by default
     *
     * @param CalendarDefinition $calendar
     * @depends testSaveCalendarInformation
     */
    public function testGetAssignmentTask(CalendarDefinition $calendar)
    {
        $task = factory(Task::class)->create();

        $this->calendar->saveCalendarAssignment([
            'OBJECT_UID' => $task->TAS_UID,
            'OBJECT_TYPE' => 'Calendar test',
            'CALENDAR_UID' => $calendar->CALENDAR_UID,
            'CALENDAR_ID' => $calendar->CALENDAR_ID,
        ]);
        $user = new User();
        $user->USR_UID = '';

        $result = $this->calendar->getCalendarAssignment($user, new Process(), $task)->toArray();

        $this->verifyStructure($result, true, true);
        //verify calendar owner
        $this->assertArrayHasKey('OWNER', $result);
        $this->assertEquals($result['OWNER'], 'TASK');
    }

    /**
     * Test validate calendar CALENDAR WORK DAYS and return calendar default
     */
    public function testValidateCalendarException()
    {
        $faker = Faker::create();
        $calendar = [
            'CALENDAR_WORK_DAYS' => $faker->randomElement(['1|2', '1|3', '2'])
        ];
        $result = $this->calendar->validateCalendarInformation($calendar);
        $this->assertEquals($result['CALENDAR_UID'], '00000000000000000000000000000001');
    }

    /**
     * Test validate calendar data business days
     */
    public function testValidateCalendarBusinessDay()
    {
        $faker = Faker::create();
        $calendar = [
            'CALENDAR_WORK_DAYS' => $faker->randomElement(['1|2|3|4|5', '1|3|5', '2|4|6']),
            'BUSINESS_DAY' => []
        ];
        $result = $this->calendar->validateCalendarInformation($calendar);
        $this->assertEquals($result['CALENDAR_UID'], '00000000000000000000000000000001');
    }

    /**
     * Verify structure
     *
     * @param array $data
     * @param bool $business
     * @param bool $holiday
     */
    private function verifyStructure($data, $business = false, $holiday = false)
    {
        $this->assertArrayHasKey('CALENDAR_UID', $data);
        $this->assertArrayHasKey('CALENDAR_NAME', $data);
        $this->assertArrayHasKey('CALENDAR_DESCRIPTION', $data);
        $this->assertArrayHasKey('CALENDAR_STATUS', $data);
        $this->assertArrayHasKey('CALENDAR_WORK_DAYS', $data);
        if ($business) {
            $this->assertArrayHasKey('BUSINESS_DAY', $data);
            $this->assertInternalType('array', $data['BUSINESS_DAY']);
            foreach ($data['BUSINESS_DAY'] as $businessDay) {
                $this->assertArrayHasKey('CALENDAR_BUSINESS_DAY', $businessDay);
                $this->assertArrayHasKey('CALENDAR_BUSINESS_START', $businessDay);
                $this->assertArrayHasKey('CALENDAR_BUSINESS_END', $businessDay);
            }
        }
        if ($holiday) {
            $this->assertArrayHasKey('HOLIDAY', $data);
            $this->assertInternalType('array', $data['HOLIDAY']);
            foreach ($data['HOLIDAY'] as $businessDay) {
                $this->assertArrayHasKey('CALENDAR_HOLIDAY_NAME', $businessDay);
                $this->assertArrayHasKey('CALENDAR_HOLIDAY_START', $businessDay);
                $this->assertArrayHasKey('CALENDAR_HOLIDAY_END', $businessDay);
            }
        }
    }
}