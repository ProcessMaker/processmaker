<?php

namespace ProcessMaker\Tests\Feature\Etag;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

class HandleEtagCacheInvalidationTest extends TestCase
{
    use WithFaker;

    protected $faker;

    private string $response = 'OK';

    private const TEST_ROUTE = '/_test/etag-cache-invalidation';

    public function setUp(): void
    {
        parent::setUp();

        // Define a route with the etag middleware and etag_tables default.
        Route::middleware('etag')
            ->get(self::TEST_ROUTE, function () {
                return response($this->response, 200);
            })
            ->defaults('etag_tables', 'processes');
    }

    public function testEtagInvalidatesOnDatabaseUpdate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a process record to simulate database changes.
        $process = Process::factory()->create([
            'updated_at' => now()->yesterday(),
        ]);

        // First request: Get the initial ETag.
        $response = $this->get(self::TEST_ROUTE);
        $initialEtag = $response->headers->get('ETag');
        $this->assertNotNull($initialEtag, 'Initial ETag was set');

        // Simulate a database update by changing `updated_at`.
        $process->update(['name' => $this->faker->name]);

        // Second request: ETag should change due to the database update.
        $responseAfterUpdate = $this->get(self::TEST_ROUTE);
        $newEtag = $responseAfterUpdate->headers->get('ETag');

        $this->assertNotNull($newEtag, 'New ETag was set after database update');
        $this->assertNotEquals($initialEtag, $newEtag, 'ETag changed after database update');

        // Third request: Simulate a client sending the old ETag.
        $responseWithOldEtag = $this->withHeaders(['If-None-Match' => $initialEtag])
            ->get(self::TEST_ROUTE);

        $responseWithOldEtag->assertStatus(200);
        $responseWithOldEtag->assertHeader('ETag', $newEtag, 'Response did not return the updated ETag');
    }
}
