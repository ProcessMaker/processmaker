<?php

namespace Tests\Unit\ProcessMaker\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ProcessMaker\Services\InvalidBearerTokenCache;

class InvalidBearerTokenCacheTest extends TestCase
{
    private Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new Repository(new ArrayStore());
        InvalidBearerTokenCache::setRepositoryForTesting($this->cache);
        InvalidBearerTokenCache::setTtlSecondsForTesting(60);
    }

    protected function tearDown(): void
    {
        InvalidBearerTokenCache::setRepositoryForTesting(null);
        InvalidBearerTokenCache::setTtlSecondsForTesting(null);
        parent::tearDown();
    }

    public function testRememberPassportJwtFailureMarksTokenForSkipPassport(): void
    {
        $token = 'aaa.bbb.ccc';
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->assertFalse(InvalidBearerTokenCache::isInNegativeCache($token));

        InvalidBearerTokenCache::rememberPassportJwtFailure($request, ['api']);

        $this->assertTrue(InvalidBearerTokenCache::isInNegativeCache($token));
    }

    public function testPrepareApiBearerBeforePassportThrowsWhenTokenWasCached(): void
    {
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', 'Bearer aaa.bbb.ccc');

        InvalidBearerTokenCache::rememberPassportJwtFailure($request, ['api']);

        $this->expectException(AuthenticationException::class);

        InvalidBearerTokenCache::prepareApiBearerBeforePassport($request, ['api']);
    }

    public function testPrepareApiBearerBeforePassportRejectsBearerWithoutJwtShape(): void
    {
        $request = $this->requestWithAuthorization('Bearer FAKE_JWT');

        $this->expectException(AuthenticationException::class);

        InvalidBearerTokenCache::prepareApiBearerBeforePassport($request, ['api']);
    }

    public function testPrepareApiBearerBeforePassportAllowsJwtShapedBearerThrough(): void
    {
        $request = $this->requestWithAuthorization('Bearer aaa.bbb.ccc');

        InvalidBearerTokenCache::prepareApiBearerBeforePassport($request, ['api']);
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportIgnoresEmptyBearer(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            $this->requestWithAuthorization('Bearer '),
            ['api']
        );
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportIgnoresWhitespaceOnlyBearer(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            $this->requestWithAuthorization('Bearer    '),
            ['api']
        );
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportAppliesWhenApiGuardAmongMultiple(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            $this->requestWithAuthorization('Bearer aaa.bbb.ccc'),
            ['web', 'api']
        );
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportSkipsNonApiGuard(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            $this->requestWithAuthorization('Bearer FAKE_JWT'),
            ['web']
        );
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportSkipsWithoutBearerHeader(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            Request::create('/api/1.0/tasks', 'GET'),
            ['api']
        );
        $this->addToAssertionCount(1);
    }

    public function testPrepareApiBearerBeforePassportSkipsNonBearerScheme(): void
    {
        InvalidBearerTokenCache::prepareApiBearerBeforePassport(
            $this->requestWithAuthorization('Basic dXNlcjpwYXNz'),
            ['api']
        );
        $this->addToAssertionCount(1);
    }

    public function testDoesNotCacheMalformedTokensFromRequest(): void
    {
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', 'Bearer FAKE_JWT');

        InvalidBearerTokenCache::rememberPassportJwtFailure($request, ['api']);

        $this->assertFalse(InvalidBearerTokenCache::isInNegativeCache('FAKE_JWT'));
    }

    public function testRememberPassportJwtFailureUsesStashedTokenAfterPassportClearsHeader(): void
    {
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', 'Bearer aaa.bbb.ccc');
        InvalidBearerTokenCache::prepareApiBearerBeforePassport($request, ['api']);
        $request->headers->remove('Authorization');

        InvalidBearerTokenCache::rememberPassportJwtFailure($request, ['api']);

        $this->assertTrue(InvalidBearerTokenCache::isInNegativeCache('aaa.bbb.ccc'));
    }

    private function requestWithAuthorization(string $authorization): Request
    {
        $request = Request::create('/api/1.0/tasks', 'GET');
        $request->headers->set('Authorization', $authorization);

        return $request;
    }
}
