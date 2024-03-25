<?php

namespace Tests\Jobs;

use Facades\ProcessMaker\InboxRules\ApplyAction;
use Facades\ProcessMaker\InboxRules\MatchingTasks;
use ProcessMaker\Jobs\SmartInbox;
use ProcessMaker\Models\InboxRule;
use ProcessMaker\Models\ProcessRequestToken;
use Tests\TestCase;

class SmartInboxTest extends TestCase
{
    public function testJobIsCalledWhenTaskIsAssigned()
    {
        $task = ProcessRequestToken::factory()->create();
        $inboxRule = InboxRule::factory()->create();

        MatchingTasks::shouldReceive('matchingInboxRules')
            ->once()
            ->with(\Mockery::on(fn ($arg) => $arg instanceof ProcessRequestToken && $arg->id === $task->id))
            ->andReturn([$inboxRule]);

        ApplyAction::shouldReceive('applyActionOnTask')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($task) {
                return $arg instanceof ProcessRequestToken && $arg->id === $task->id;
            }), \Mockery::on(function ($arg) use ($inboxRule) {
                return $arg instanceof InboxRule && $arg->id === $inboxRule->id;
            }));

        SmartInbox::dispatch($task->id);
    }
}
