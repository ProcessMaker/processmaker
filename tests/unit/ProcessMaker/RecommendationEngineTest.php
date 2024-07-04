<?php

namespace Tests\unit\ProcessMaker;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\User;
use ProcessMaker\SyncRecommendations;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    private static array $generatedModelUuids;

    private SyncRecommendations $syncRecommendations;

    public function __construct()
    {
        parent::__construct();

        $this->syncRecommendations = app(SyncRecommendations::class);
    }

    public function testRecommendationMatchesAndAccuracy(): void
    {
        $user = User::factory()->create([
            'status' => 'ACTIVE',
        ]);

        $processRequest = ProcessRequest::factory()->create([
            'data' => ['price' => random_int(512, 2048)],
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
                ],
            ],
        ]);

        $processRequestTokens = ProcessRequestToken::factory(3)->create([
            'user_id' => $user->id,
            'process_request_id' => $processRequest->id,
            'process_id' => $processRequest->process_id,
            'status' => 'active',
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

    public function testRecommendationsSync(): void
    {
        Http::preventStrayRequests();

        // Set up the fake responses for the
        Http::fake([
            // Index file contains all directories/filenames in the repo
            'https://api.github.com/repos/processmaker/pm4-recommendations/contents*' => Http::response([
                [
                    'name' => 'default',
                    'type' => 'dir',
                    'url' => 'https://repo.test/default',
                ],
                [
                    'name' => 'localhost',
                    'type' => 'dir',
                    'url' => 'https://repo.test/localhost',
                ],
            ]),
            // Default directory
            'https://repo.test/default' => Http::response([
                [
                    'download_url' => 'https://repo.test/default/global_test.json',
                ],
            ]),
            // Matches with the instance domain
            // the unit tests are running on
            'https://repo.test/localhost' => Http::response([
                [
                    'download_url' => 'https://repo.test/localhost/instance_test.json',
                ],
            ]),
            'https://repo.test/default/global_test.json' => Http::response($this->generateModelData()),
            'https://repo.test/localhost/instance_test.json' => Http::response($this->generateModelData()),
        ]);

        // Run the sync
        $this->syncRecommendations->sync();

        // Build a query to count the recommendation
        // persisted with the uuids we generated
        $persistedRecommendationQuery = Recommendation::query()->whereIn('uuid', static::$generatedModelUuids);

        // Compare the number of recommendations we
        // persisted from the generated model data
        $this->assertCount($persistedRecommendationQuery->count(), static::$generatedModelUuids);
    }

    /**
     * Use a factory to generate a Recommendation model's data
     * without persisting it to use as mock JSON responses
     *
     * @return array
     */
    protected function generateModelData(): array
    {
        // Generate the mock data
        $model_data = Recommendation::factory()->make([
            'advanced_filter' => '[{"subject":{"type":"Field","value":"process_request_id"},"operator":"!=","value":1}]',
        ]);

        // Save the generated uuid to check if the recommendation
        // was persisted during the test
        static::$generatedModelUuids[] = $model_data->uuid;

        // Return an anonymous object, which will represent
        // the json body of the mock http response
        return $model_data->toArray();
    }
}
