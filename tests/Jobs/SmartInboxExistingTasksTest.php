<?php

namespace Tests\Jobs;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Jobs\SmartInboxExistingTasks;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class SmartInboxExistingTasksTest extends TestCase
{
    public function testJobIsCalledToExistingTasksFromInboxRule()
    {
        $task = ProcessRequestToken::factory()->create();
        $inboxRule = InboxRule::factory()->create();

        MatchingTasks::shouldReceive('get')
        ->once()
        ->with(\Mockery::on(fn ($arg) => $arg instanceof InboxRule && $arg === $inboxRule))
        ->andReturn([$task]);

        ApplyAction::shouldReceive('applyActionOnTask')
            ->once()
            ->with(
                \Mockery::on(function ($arg) use ($task) {
                    return $arg instanceof ProcessRequestToken && $arg->id === $task->id;
                }),
                [$inboxRule]
            );

        SmartInboxExistingTasks::dispatch($inboxRule->id);
    }
}
