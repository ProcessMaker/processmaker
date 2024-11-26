<?php

namespace Werk365\EtagConditionals\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Middleware\Etag\IfNoneMatch;
use Tests\TestCase;

class IfNoneMatchTest extends TestCase
{
    private string $response = 'OK';

    private const TEST_ROUTE = '/_test/if-none-match';

    public function setUp(): void
    {
        parent::setUp();

        Route::middleware(IfNoneMatch::class)->any(self::TEST_ROUTE, function () {
            return response($this->response, 200);
        });
    }

    public function testGetRequestStatus200WithNoneMatchingIfNoneMatch()
    {
        $noneMatch = '"' . md5($this->response . 'NoneMatch') . '"';
        $response = $this
            ->withHeaders([
                'If-None-Match' => $noneMatch,
            ])
            ->get(self::TEST_ROUTE);

        $response->assertStatus(200);
    }

    public function testGetRequestStatus304WithMatchingIfNoneMatch()
    {
        $noneMatch = '"' . md5($this->response) . '"';
        $response = $this
            ->withHeaders([
                'If-None-Match' => $noneMatch,
            ])
            ->get(self::TEST_ROUTE);

        $response->assertStatus(304);
    }
}
