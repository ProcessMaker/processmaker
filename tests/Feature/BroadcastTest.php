<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Events\ActivityCompleted;
use ProcessMaker\Events\BuildScriptExecutor;
use ProcessMaker\Events\ImportedScreenSaved;
use ProcessMaker\Events\ModelerStarting;
use ProcessMaker\Events\ProcessCompleted;
use ProcessMaker\Events\ProcessUpdated;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Events\SessionStarted as SessionStartedEvent;
use ProcessMaker\Events\TestStatusEvent;
use ProcessMaker\Managers\ModelerManager as Modeler;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Managers\ScreenBuilderManager as ScreenBuilder;
use ProcessMaker\Models\ProcessRequest as Request;
use ProcessMaker\Models\ProcessRequestToken as Task;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\LoggingHelper;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class BroadcastTest extends TestCase
{
    use LoggingHelper;
    use WithFaker;

    public function testBroadcastEventsHaveTesting(): void
    {
        $this->markTestSkipped('FOUR-6653');

        foreach (scandir(app_path('Events')) as $file) {
            if (!preg_match('/(?<name>.+).php/', $file, $matches)) {
                continue;
            }

            $name = $matches['name'];
            $methodName = "test{$name}Broadcast";

            $this->assertTrue(method_exists($this, $methodName), "Failed asserting that broadcast event {$name} has a test.");
        }
    }

    /**
     * Test that the SettingsLoaded event was fired during the Application boot up.
     */
    public function testSettingsLoadedBroadcast(): void
    {
        $this->markTestSkipped('FOUR-6653');

        $this->assertTrue(config('app.settings.loaded'));
    }

    /**
     * Asserts that the ActivityAssigned broadcast event works.
     */
    public function testActivityAssignedBroadcast(): void
    {
        $task = Task::factory()->create([
            'data' => [
                'test' => $this->faker->text(20000),
            ],
        ]);

        event(new ActivityAssigned($task));
        $this->assertLogContainsText('ActivityAssigned');
        $this->assertLogContainsText(addcslashes(route('api.tasks.show', ['task' => $task->id]), '/'));
        $this->assertBroadcastEventSizeLessThan('ActivityAssigned', 10000);
    }

    /**
     * Asserts that the ActivityCompleted broadcast event works.
     */
    public function testActivityCompletedBroadcast(): void
    {
        $task = Task::factory()->create([
            'data' => [
                'test' => $this->faker->text(20000),
            ],
        ]);
        event(new ActivityCompleted($task));
        $this->assertLogContainsText('ActivityCompleted');
        $this->assertLogContainsText(addcslashes(route('api.tasks.show', ['task' => $task->id]), '/'));
        $this->assertBroadcastEventSizeLessThan('ActivityCompleted', 10000);
    }

    /**
     * Asserts that the ProcessCompleted broadcast event works.
     */
    public function testProcessCompletedBroadcast(): void
    {
        $request = Request::factory()->create([
            'data' => [
                'test' => $this->faker->text(20000),
            ],
        ]);
        event(new ProcessCompleted($request));
        $this->assertLogContainsText('ProcessCompleted');
        $this->assertLogContainsText(addcslashes(route('api.requests.show', [$request->id]), '/'));
        $this->assertBroadcastEventSizeLessThan('ProcessCompleted', 10000);
    }

    /**
     * Asserts that the ProcessUpdated broadcast event works.
     */
    public function testProcessUpdatedBroadcast(): void
    {
        $request = Request::factory()->create([
            'data' => [
                'test' => $this->faker->text(20000),
            ],
        ]);
        event(new ProcessUpdated($request, 'ACTIVITY_COMPLETED'));
        $this->assertLogContainsText('ProcessUpdated');
        $this->assertLogContainsText(addcslashes(route('api.requests.show', [$request->id]), '/'));
        $this->assertBroadcastEventSizeLessThan('ProcessUpdated', 10000);
    }

    /**
     * Asserts that the ScreenBuilderStarting broadcast event works.
     */
    public function testScreenBuilderStartingBroadcast(): void
    {
        Event::fake();
        $manager = new ScreenBuilder();
        event(new ScreenBuilderStarting($manager, 'DISPLAY'));

        Event::assertDispatched(ScreenBuilderStarting::class);
    }

    /**
     * Asserts that the ScreenBuilderStarting broadcast event works.
     */
    public function testModelerStartingBroadcast(): void
    {
        Event::fake();
        $manager = new Modeler();
        event(new ModelerStarting($manager));

        Event::assertDispatched(ModelerStarting::class);
    }

    /**
     * Asserts that the SessionStart broadcast event works.
     */
    public function testSessionStartedBroadcast(): void
    {
        $user = User::factory()->create();
        event(new SessionStartedEvent($user));

        $this->assertLogContainsText('SessionStarted');
        $this->assertBroadcastEventSizeLessThan('SessionStarted', 10000);
    }

    /**
     * Asserts that the BuildScriptExecutor event works.
     */
    public function testBuildScriptExecutorBroadcast(): void
    {
        $user = User::factory()->create();
        event(new BuildScriptExecutor('output-text', $user->id, 'output-status'));

        $this->assertLogContainsText('output-text');
        $this->assertLogContainsText((string) $user->id);
        $this->assertLogContainsText('output-status');
    }

    /**
     * Asserts that the ScreenBuilderStarting broadcast event works.
     */
    public function testScriptBuilderStartingBroadcast(): void
    {
        Event::fake();
        $manager = new ScreenBuilderManager();
        $type = 'FORM';
        event(new ScreenBuilderStarting($manager, $type));

        Event::assertDispatched(ScreenBuilderStarting::class);
    }

    /**
     * Asserts that the BuildScriptExecutor event works.
     */
    public function testImportedScreenSavedBroadcast(): void
    {
        Event::fake();
        $screen = Screen::factory()->create();
        event(new ImportedScreenSaved($screen->id, $screen->toArray()));

        Event::assertDispatched(ImportedScreenSaved::class);
    }

    /**
     * Asserts that the TestStatusEvent event works.
     */
    public function testTestStatusEventBroadcast(): void
    {
        Event::fake();
        event(new TestStatusEvent('test', 'test status event'));

        Event::assertDispatched(TestStatusEvent::class);
    }

    /**
     * Asserts that the BuildScriptExecutor event works.
     */
    public function testScriptResponseEventBroadcast(): void
    {
        Event::fake();
        $user = User::factory()->create();
        event(new ScriptResponseEvent($user, 200, ['foo' => 'bar'], ['config_one' => 1], 'nonce001'));
        Event::assertDispatched(ScriptResponseEvent::class);
    }
}
