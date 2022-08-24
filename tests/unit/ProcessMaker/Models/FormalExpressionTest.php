<?php

namespace ProcessMaker\Models;

use Carbon\Carbon;
use DateTime;
use Tests\TestCase;

class FormalExpressionTest extends TestCase
{
    public function testDateFunction()
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');

        // Validate that feel date function works as php date:
        $formalExp->setBody("date('Y-m-d H:i')");
        $feelDateInMinutes = $formalExp([]);
        $currentTimeInMinutes = date('Y-m-d H:i');
        $this->assertEquals($currentTimeInMinutes, $feelDateInMinutes);

        // Validate other format (hours only)
        $formalExp->setBody("date('H')");
        $feelCurrentHour = $formalExp([]);
        $currentHour = date('H');
        $this->assertEquals($currentHour, $feelCurrentHour);

        // Validate that timestamp for function date works
        $timestamp = time();
        $formalExp->setBody("date('Y-m-d H:i:s', $timestamp)");
        $feelDate = $formalExp([]);
        $date = date('Y-m-d H:i:s');
        $this->assertEquals($date, $feelDate);
    }

    public function testDateFunctionWithTimeZone()
    {
        $formalExp = new FormalExpression();
        $formalExp->setLanguage('FEEL');

        // Validate that feel date function works as php DateTime:
        $formalExp->setBody("date('Y-m-d H:i', null, 'Africa/Djibouti')");
        $feelDateInMinutes = $formalExp([]);
        $currentTimeInMinutes = new DateTime('now', new \DateTimeZone('Africa/Djibouti'));
        $this->assertEquals($currentTimeInMinutes->format('Y-m-d H:i'), $feelDateInMinutes);

        // Validate that timestamp for function date works
        $timestamp = time();
        $formalExp->setBody("date('Y-m-d H:i:s', $timestamp, 'Africa/Djibouti')");
        $feelDate = $formalExp([]);
        $date = new DateTime('now', new \DateTimeZone('Africa/Djibouti'));
        $date->setTimestamp($timestamp);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $feelDate);

        // Set the current date and time in UTC for this test
        $testNow = Carbon::setTestNow(new Carbon('2021-08-31 12:00:00', 'UTC'));
        // Time config to test
        $userScheduleTime = '08:00';
        $formalExp->setBody("date('H:i', null, 'America/La_Paz')");
        $feelDate = $formalExp([]);

        // Check the expression date('H:i', null, user_tz) == user_schedule_time
        $this->assertTrue($userScheduleTime === $feelDate);
    }
}
