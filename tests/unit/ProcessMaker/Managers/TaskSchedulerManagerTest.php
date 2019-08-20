<?php

namespace ProcessMaker\Managers;

use Carbon\Carbon;
use DateTime;
use Tests\TestCase;

class TaskSchedulerManagerTest extends TestCase
{

    /**
     *
     * @var TaskSchedulerManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::__construct();
        $this->manager = new TaskSchedulerManager;
    }

    public function testRoundDates()
    {
        // Exact minute:                 00:01:00 -> 00:01:00
        $date = new DateTime('2019-08-14 00:01:00');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 10 seconds:            00:01:10 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:10');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 29 seconds:            00:01:29 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:29');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 30 seconds:            00:01:30 -> 00:02:00
        $date = new Carbon('2019-08-14 00:01:30');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:02:00', $rounded->format('H:i:s'));

        // with 59 seconds:            00:01:59 -> 00:02:00
        $date = new Carbon('2019-08-14 00:01:59');
        $rounded = $this->manager->roundDateTime($date);
        $this->assertEquals('00:02:00', $rounded->format('H:i:s'));
    }

    public function testTruncateDates()
    {
        // Exact minute:                 00:01:00 -> 00:01:00
        $date = new DateTime('2019-08-14 00:01:00');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 10 seconds:            00:01:10 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:10');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 29 seconds:            00:01:29 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:29');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 30 seconds:            00:01:30 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:30');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));

        // with 59 seconds:            00:01:59 -> 00:01:00
        $date = new Carbon('2019-08-14 00:01:59');
        $rounded = $this->manager->truncateDateTime($date);
        $this->assertEquals('00:01:00', $rounded->format('H:i:s'));
    }
}
