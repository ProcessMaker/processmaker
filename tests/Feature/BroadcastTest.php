<?php
namespace Tests\Feature;

use Tests\TestCase;
use Tests\Feature\Shared\LoggingHelper;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Models\ProcessRequestToken as Task;

class BroadcastTest extends TestCase
{
    use LoggingHelper;

    /**
     * Asserts that the ActivityAssigned broadcast event works.
     *
     * @return void
     */
    public function testActivityAssignedBroadcast()
    {
        $task = factory(Task::class)->create();
        event(new ActivityAssigned($task));
        $this->assertLogContainsText('ActivityAssigned');
        $this->assertLogContainsText(addcslashes(route('api.tasks.show', ['task' => $task->id]), '/'));
    }
}
