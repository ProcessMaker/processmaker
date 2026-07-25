<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Console\Scheduling\FastCommandEvent;
use ProcessMaker\Console\Scheduling\FastSchedule;
use Tests\TestCase;

class FastScheduleTest extends TestCase
{
    public function testContainerResolvesFastSchedule()
    {
        $this->assertInstanceOf(FastSchedule::class, app(Schedule::class));
    }

    public function testCommandCreatesFastCommandEventAndAutoNames()
    {
        $schedule = new FastSchedule();
        $event = $schedule->command('foo:bar --queue');

        $this->assertInstanceOf(FastCommandEvent::class, $event);
        $this->assertSame('foo:bar --queue', $event->description);
    }

    public function testCommandPreservesExplicitName()
    {
        $schedule = new FastSchedule();
        $event = $schedule->command('foo:bar')->name('custom-name');

        $this->assertInstanceOf(FastCommandEvent::class, $event);
        $this->assertSame('custom-name', $event->description);
    }

    public function testCommandFallsBackToShellEventForRedirects()
    {
        $schedule = new FastSchedule();
        $event = $schedule->command('cache:metrics --format=json > storage/logs/metrics.json');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertNotInstanceOf(FastCommandEvent::class, $event);
    }

    public function testRunInBackgroundKeepsShellFallbackFlag()
    {
        $schedule = new FastSchedule();
        $event = $schedule->command('cases:retention:evaluate')->runInBackground();

        $this->assertInstanceOf(FastCommandEvent::class, $event);
        $this->assertTrue($event->runInBackground);
    }

    public function testCommandRunsInProcessViaArtisanCall()
    {
        Artisan::registerCommand(app(FastScheduleProbeCommand::class));

        app()->instance('fast-schedule-probe-token', 'in-process');
        FastScheduleProbeCommand::$seenToken = null;

        $schedule = new FastSchedule();
        $event = $schedule->command('fast-schedule:probe');

        $this->assertInstanceOf(FastCommandEvent::class, $event);

        $event->run(app());

        $this->assertSame(
            'in-process',
            FastScheduleProbeCommand::$seenToken,
            'Command should see container state from the current process'
        );
    }
}

class FastScheduleProbeCommand extends Command
{
    public static ?string $seenToken = null;

    protected $signature = 'fast-schedule:probe';

    protected $description = 'Probe command for FastSchedule tests';

    public function handle(): int
    {
        self::$seenToken = app()->bound('fast-schedule-probe-token')
            ? app('fast-schedule-probe-token')
            : null;

        return self::SUCCESS;
    }
}
