<?php

namespace ProcessMaker\Tests\Feature\Etag;

use Illuminate\Http\Request;
use ProcessMaker\Http\Resources\Caching\EtagManager;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EtagManagerTest extends TestCase
{
    private string $response = 'OK';

    public function tearDownEtag()
    {
        EtagManager::etagGenerateUsing(null);
    }

    public function testGetDefaultEtag()
    {
        $request = Request::create('/', 'GET');
        $response = response($this->response, 200);

        $this->assertEquals('"e0aa021e21dddbd6d8cecec71e9cf564"', EtagManager::getEtag($request, $response));
    }

    public function testGetEtagWithCallbackMd5()
    {
        $request = Request::create('/', 'GET');
        $response = response($this->response, 200);

        EtagManager::etagGenerateUsing(function (Request $request, Response $response) {
            return md5($response->getContent());
        });

        $this->assertEquals('"e0aa021e21dddbd6d8cecec71e9cf564"', EtagManager::getEtag($request, $response));
    }

    public function testGetEtagWithCallbackSha256()
    {
        $request = Request::create('/', 'GET');
        $response = response($this->response, 200);

        EtagManager::etagGenerateUsing(function (Request $request, Response $response) {
            return hash('sha256', $response->getContent());
        });

        $expectedHash = hash('sha256', $this->response);
        $this->assertEquals("\"$expectedHash\"", EtagManager::getEtag($request, $response));
    }
}
