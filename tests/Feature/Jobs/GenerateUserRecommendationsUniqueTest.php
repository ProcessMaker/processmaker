<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use Illuminate\Support\Facades\Queue;
use ProcessMaker\Events\ActivityCompleted;
use ProcessMaker\Jobs\GenerateUserRecommendations;
use ProcessMaker\Jobs\SmartInbox;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
use Tests\TestCase;

class GenerateUserRecommendationsUniqueTest extends TestCase
{
    public function test_duplicate_dispatches_for_the_same_user_are_dropped(): void
    {
        Queue::fake();

        $user = User::factory()->create(['status' => 'ACTIVE']);

        GenerateUserRecommendations::dispatch($user->id);
        GenerateUserRecommendations::dispatch($user->id)->onQueue('low');

        Queue::assertPushed(GenerateUserRecommendations::class, 1);
    }

    public function test_smart_inbox_and_activity_completed_do_not_enqueue_twice_for_the_same_user(): void
    {
        Queue::fake();

        $user = User::factory()->create(['status' => 'ACTIVE']);
        $token = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'element_type' => 'task',
            'status' => 'CLOSED',
        ]);

        (new SmartInbox($token->id))->handle();
        event(new ActivityCompleted($token));

        Queue::assertPushed(GenerateUserRecommendations::class, 1);
        Queue::assertPushed(GenerateUserRecommendations::class, function (GenerateUserRecommendations $job) use ($user) {
            return $job->user_id === $user->id;
        });
    }
}
