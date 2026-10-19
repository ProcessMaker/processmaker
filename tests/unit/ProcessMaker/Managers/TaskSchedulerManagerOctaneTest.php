<?php

namespace Tests\Unit\ProcessMaker\Managers;

use Carbon\Carbon;
use ProcessMaker\Managers\TaskSchedulerManager;
use Tests\TestCase;

class TaskSchedulerManagerOctaneTest extends TestCase
{
    /**
     * Test that fakeToday on one instance does not leak to another instance.
     * This is the Octane data leak scenario: static properties persist across requests.
     */
    public function testFakeTodayDoesNotLeakBetweenInstances()
    {
        // Instance 1: set a fake date
        $manager1 = new TaskSchedulerManager();
        $manager1->fakeToday('2020-01-15T10:30:00Z');

        // Instance 2: should NOT see the fake date from instance 1
        $manager2 = new TaskSchedulerManager();
        $today2 = $manager2->today();

        // Instance 2 should return the real current date, not the fake one
        $this->assertNotEquals('2020-01-15', $today2->format('Y-m-d'));

        // Cleanup
        $manager1->fakeToday(null);
    }

    /**
     * Test that fakeToday persists within the same instance.
     */
    public function testFakeTodayPersistsWithinSameInstance()
    {
        $manager = new TaskSchedulerManager();
        $manager->fakeToday('2020-01-15T10:30:00Z');

        $today = $manager->today();

        $this->assertEquals('2020-01-15', $today->format('Y-m-d'));
        $this->assertEquals('10:30:00', $today->format('H:i:s'));

        $manager->fakeToday(null);
    }

    /**
     * Test that fakeToday(null) resets the date to the real current date.
     */
    public function testFakeTodayNullResetsToRealDate()
    {
        $manager = new TaskSchedulerManager();
        $manager->fakeToday('2020-01-15T10:30:00Z');

        // Reset
        $manager->fakeToday(null);

        $today = $manager->today();
        $this->assertNotEquals('2020-01-15', $today->format('Y-m-d'));
    }

    /**
     * Test that Carbon::setTestNow is also reset when fakeToday(null) is called.
     */
    public function testCarbonTestNowIsReset()
    {
        $manager = new TaskSchedulerManager();
        $manager->fakeToday('2020-01-15T10:30:00Z');

        // Carbon::now() should return the fake date
        $this->assertEquals('2020-01-15', Carbon::now()->format('Y-m-d'));

        // Reset
        $manager->fakeToday(null);

        // Carbon::now() should return the real date
        $this->assertNotEquals('2020-01-15', Carbon::now()->format('Y-m-d'));
    }

    /**
     * Test that multiple instances can have different fake dates simultaneously.
     * This simulates concurrent requests in Octane.
     */
    public function testMultipleInstancesCanHaveDifferentFakeDates()
    {
        $manager1 = new TaskSchedulerManager();
        $manager1->fakeToday('2020-01-15T10:30:00Z');

        $manager2 = new TaskSchedulerManager();
        $manager2->fakeToday('2021-06-20T08:00:00Z');

        $this->assertEquals('2020-01-15', $manager1->today()->format('Y-m-d'));
        $this->assertEquals('2021-06-20', $manager2->today()->format('Y-m-d'));

        $manager1->fakeToday(null);
        $manager2->fakeToday(null);
    }
}