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
        ->with(\Mockery::on(fn ($arg) => $arg instanceof InboxRule && optional($arg)->id === $inboxRule->id))
        ->andReturn(collect([$task]));

        ApplyAction::shouldReceive('applyActionOnTask')
            ->once()
            ->with(
                \Mockery::on(function ($arg1) use ($task) {
                    return $arg1 instanceof ProcessRequestToken && $arg1->id === $task->id;
                }),
                \Mockery::on(function ($arg2) use ($inboxRule) {
                    return $arg2 instanceof InboxRule && $arg2->id === $inboxRule->id;
                })
            );

        SmartInboxExistingTasks::dispatch($inboxRule->id);
    }
}
