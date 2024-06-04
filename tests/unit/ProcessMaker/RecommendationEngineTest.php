<?php

namespace Tests\unit\ProcessMaker;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Events\ActivityAssigned;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Recommendation;
use ProcessMaker\Models\RecommendationUser;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\User;
use ProcessMaker\SyncRecommendations;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    private static string $appUrl;

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
        // Swap out the app's url for this test
        static::swapAppUrl();

        Http::preventStrayRequests();

        // Create the url patterns simulating the file urls in the repo
        $index_file_url = $this->syncRecommendations->url('index.json');
        $default_dir_url = $this->syncRecommendations->url('default/*.json');
        $local_dir_url = $this->syncRecommendations->url('local.test/*.json');

        // Set up the fake responses for the
        Http::fake([
            // Index file contains all directories/filenames in the repo
            $index_file_url => Http::response($this->generateIndexJsonFileContents()),
            // Default directory
            $default_dir_url => Http::response($this->generateModelData()),
            // Matches with the instance domain
            // the unit tests are running on
            $local_dir_url => Http::response($this->generateModelData()),
        ]);

        // Run the sync
        $this->syncRecommendations->sync();

        // Build a query to count the recommendation
        // persisted with the uuids we generated
        $persistedRecommendationQuery = Recommendation::query()->whereIn('uuid', static::$generatedModelUuids);

        // Compare the number of recommendations we
        // persisted from the generated model data
        $this->assertCount($persistedRecommendationQuery->count(), static::$generatedModelUuids);

        // Put the original app url back in the config
        static::swapAppUrl();
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

    /**
     * Generate simulated json file contents standing in
     * for the index.json file contained in the repo
     *
     * @return array
     */
    protected function generateIndexJsonFileContents(): array
    {
        $generateFileName = static fn () => Str::random().'.json';

        return [
            // The default recommendations for all instances
            'default' => [
                $generateFileName()
            ],

            // Every other directory name is checked against the
            // instance's domain for a match. If it matches, it
            // downloads/saves them. local.test is created
            // specifically for this unit test.
            'local.test' => [
                $generateFileName(),
                $generateFileName(),
            ],
        ];
    }

    /**
     * Swap the app URL in the configuration for use by a testing
     * recommendations meant for specific instances
     *
     * @return void
     */
    protected static function swapAppUrl(): void
    {
        if (config('app.url') === 'local.test') {
            config(['app.url' => static::$appUrl]);
        } else {
            static::$appUrl = config('app.url');
            config(['app.url' => 'local.test']);
        }
    }
}
