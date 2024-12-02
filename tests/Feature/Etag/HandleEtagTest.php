<?php

namespace ProcessMaker\Tests\Feature\Etag;

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Middleware\Etag\HandleEtag;
use ProcessMaker\Http\Resources\Caching\EtagManager;
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
        Route::middleware(HandleEtag::class)->any(self::TEST_ROUTE, function () {
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

        Route::middleware('etag:user')->any(self::TEST_ROUTE, function () {
            return response($this->response, 200);
        });

        $response = $this->get(self::TEST_ROUTE);

        $expectedEtag = '"' . md5($user->id . $this->response) . '"';
        $response->assertHeader('ETag', $expectedEtag);
    }
}
