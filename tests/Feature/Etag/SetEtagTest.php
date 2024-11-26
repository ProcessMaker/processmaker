<?php

namespace Werk365\EtagConditionals\Tests;

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Middleware\Etag\SetEtag;
use Tests\TestCase;

class SetEtagTest extends TestCase
{
    private string $response = 'OK';

    private const TEST_ROUTE = '/_test/generate-etag';

    public function setUp(): void
    {
        parent::setUp();

        Route::middleware(SetEtag::class)->any(self::TEST_ROUTE, function () {
            return $this->response;
        });
    }

    public function testMiddlewareSetsEtagHeader()
    {
        $response = $this->get(self::TEST_ROUTE);
        $response->assertHeader('ETag', null);
    }

    public function testEtagHeaderHasCorrectValue()
    {
        $value = '"' . md5($this->response) . '"';
        $response = $this->get(self::TEST_ROUTE);
        $response->assertHeader('ETag', $value);
    }
}
