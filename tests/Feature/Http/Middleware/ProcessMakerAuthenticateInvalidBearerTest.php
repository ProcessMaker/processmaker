<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use ProcessMaker\Services\InvalidBearerTokenCache;
use Tests\TestCase;

class ProcessMakerAuthenticateInvalidBearerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        InvalidBearerTokenCache::setRepositoryForTesting(new Repository(new ArrayStore()));
        InvalidBearerTokenCache::setTtlSecondsForTesting(60);
    }

    protected function tearDown(): void
    {
        InvalidBearerTokenCache::setRepositoryForTesting(null);
        InvalidBearerTokenCache::setTtlSecondsForTesting(null);

        parent::tearDown();
    }

    public function testNonJwtBearerWithoutAcceptJsonReturns401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer FAKE_JWT',
            'Accept' => 'text/html',
        ])->get('/api/1.0/tasks');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthenticated.']);
    }

    public function testCachedInvalidJwtWithoutAcceptJsonReturns401(): void
    {
        $token = 'aaa.bbb.ccc';
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        InvalidBearerTokenCache::rememberPassportJwtFailure($request, ['api']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'text/html',
        ])->get('/api/1.0/tasks');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthenticated.']);
    }
}
