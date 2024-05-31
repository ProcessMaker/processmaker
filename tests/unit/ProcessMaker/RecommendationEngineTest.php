<?php

namespace Tests\unit\ProcessMaker;

use Tests\TestCase;
use ProcessMaker\Models\User;
use Illuminate\Support\Carbon;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\ProcessRequestToken;

class RecommendationEngineTest extends TestCase
{
    public function testMatchesARecommendation()
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE'
        ]);

        $processRequest = ProcessRequest::factory()->create([
            'data' => ['price' => random_int(512, 2048)]
        ]);

        $recommendation = Recommendation::factory()->create([
            'advanced_filter' => [
                [
                    'subject' => ['type' => 'Field', 'value' => 'process_id'],
                    'operator' => '=',
                    'value' => $processRequest->process_id,
                ],
                [
                    'subject' => ['type' => 'Field', 'value' => 'data.price'],
                    'operator' => '>',
                    'value' => random_int(0, 511),
                ]
            ]
        ]);

        $processRequestTokens = ProcessRequestToken::factory(3)->create([
            'user_id' => $user->id,
            'process_request_id' => $processRequest->id,
            'process_id' => $processRequest->process_id,
            'status' => 'active'
        ]);

        event(new ActivityAssigned($processRequestTokens->first()));

        $this->assertEquals(1, RecommendationUser::count());

        $recommendationUser = RecommendationUser::first();

        $this->assertTrue($recommendation->is($recommendationUser->recommendation));

        $this->assertTrue($user->is($recommendationUser->user));

        $this->assertEquals(1, $recommendation->recommendationUsers()->count());

        $recommendationUser->dismissed_until = Carbon::parse($recommendationUser->dismissed_until)
                                                     ->subtract('year', 1);

        $recommendationUser->save();

        $this->assertTrue($recommendationUser->isExpired());

        $recommendationUser->dismiss();

        $this->assertFalse($recommendationUser->isExpired());
    }
}
