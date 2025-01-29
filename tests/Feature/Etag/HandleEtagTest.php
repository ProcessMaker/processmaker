<?php

namespace ProcessMaker\Tests\Feature\Etag;

use Illuminate\Support\Facades\Route;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\User;
use Tests\TestCase;

class HandleEtagTest extends TestCase
{
    private string $response = 'OK';

    private const TEST_ROUTE = '/_test/handle-etag';

    public function setUp(): void
    {
        parent::setUp();

        // Define a route that uses the HandleEtag middleware.
        Route::middleware('etag')->any(self::TEST_ROUTE, function () {
            return response($this->response, 200);
        });
    }

    public function testMiddlewareSetsEtagHeader()
    {
        $response = $this->get(self::TEST_ROUTE);
        $response->assertHeader('ETag');
    }

    public function testEtagHeaderHasCorrectValue()
    {
        $expectedEtag = '"' . md5($this->response) . '"';
        $response = $this->get(self::TEST_ROUTE);
        $response->assertHeader('ETag', $expectedEtag);
    }

    public function testRequestReturns200WhenIfNoneMatchDoesNotMatch()
    {
        $noneMatch = '"' . md5($this->response . 'NoneMatch') . '"';
        $response = $this
            ->withHeaders(['If-None-Match' => $noneMatch])
            ->get(self::TEST_ROUTE);

        $response->assertStatus(200);
        $response->assertHeader('ETag');
    }

    public function testRequestReturns304WhenIfNoneMatchMatches()
    {
        $matchingEtag = '"' . md5($this->response) . '"';
        $response = $this
            ->withHeaders(['If-None-Match' => $matchingEtag])
            ->get(self::TEST_ROUTE);

        $response->assertStatus(304);
        $response->assertHeader('ETag', $matchingEtag);
    }

    public function testRequestIgnoresWeakEtagsInIfNoneMatch()
    {
        $weakEtag = 'W/"' . md5($this->response) . '"';
        $response = $this
            ->withHeaders(['If-None-Match' => $weakEtag])
            ->get(self::TEST_ROUTE);

        $response->assertStatus(304);
        $response->assertHeader('ETag', '"' . md5($this->response) . '"');
    }

    public function testDefaultGetEtagGeneratesCorrectEtagWithUser()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Route::middleware('etag')->any(self::TEST_ROUTE, function () {
            return response($this->response, 200);
        });

        $response = $this->get(self::TEST_ROUTE);

        $expectedEtag = '"' . md5($user->id . $this->response) . '"';
        $response->assertHeader('ETag', $expectedEtag);
    }

    public function testReturns304NotModifiedWhenEtagMatchesTables()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Process::factory()->create([
            'updated_at' => now()->yesterday(),
        ]);

        Route::middleware('etag')->any(self::TEST_ROUTE, function () {
            return response($this->response, 200);
        })->defaults('etag_tables', 'processes');

        // Initial request to get the ETAg.
        $response = $this->get(self::TEST_ROUTE);
        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag, 'ETag should be set in the initial response');

        // Perform a second request sending the `If-None-Match`.
        $responseWithMatchingEtag = $this->withHeaders(['If-None-Match' => $etag])
            ->get(self::TEST_ROUTE);

        // Verify response is 304 Not Modified.
        $responseWithMatchingEtag->assertStatus(304);
        $this->assertEmpty($responseWithMatchingEtag->getContent(), 'Response content is not empty for 304 Not Modified');
        $this->assertEquals($etag, $responseWithMatchingEtag->headers->get('ETag'), 'ETag does not match the client-provided If-None-Match');
    }
}
