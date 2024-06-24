<?php

namespace Tests;

use Facades\ProcessMaker\ApplyRecommendation;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Mockery;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\User;
use Tests\TestCase;

class ApplyRecommendationTest extends TestCase
{
    public function testMarkAsPriority()
    {
        $user = User::factory()->create();

        $recommendation = Recommendation::factory()->create([
            'advanced_filter' => [
                [
                    'subject' => [
                        'type' => 'Field',
                        'value' => 'element_id',
                    ],
                    'operator' => '=',
                    'value' => 'node_1',
                ],
            ],
            'actions' => ['mark_as_priority'],
        ]);

        $activeTask1 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'node_1',
            'is_priority' => false,
        ]);

        $activeTask2 = ProcessRequestToken::factory()->create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'element_id' => 'node_1',
            'is_priority' => false,
        ]);

        ApplyRecommendation::run($recommendation, $user);

        $activeTask1->refresh();
        $this->assertTrue($activeTask1->is_priority);

        $activeTask2->refresh();
        $this->assertTrue($activeTask2->is_priority);
    }

    public function testReassignToUserID()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
            'status' => 'ACTIVE',
        ]);

        $userToReassignTo = User::factory()->create();

        $task = Mockery::mock(ProcessRequestToken::class);
        $task->shouldReceive('reassign')->once()->with($userToReassignTo->id, $user);

        $baseQuery = Mockery::mock(Builder::class);
        $baseQuery->allows([
            'get' => [$task],
        ]);

        $recommendation = Mockery::mock(Recommendation::class)->makePartial();
        $recommendation->actions = ['reassign_to_user'];
        $recommendation->allows([
            'baseQuery' => $baseQuery,
        ]);

        ApplyRecommendation::run($recommendation, $user, ['to_user_id' => $userToReassignTo->id]);
    }
}
