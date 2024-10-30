<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    public function testShowBundle()
    {
        $bundle = Bundle::factory()->create();
        $response = $this->apiCall('GET', route('api.devlink.local-bundle', ['bundle' => $bundle->id]));

        $response->assertStatus(200);
        $this->assertEquals($bundle->id, $response->json()['id']);
    }

    public function testAddAssets()
    {
        $screen1 = Screen::factory()->create();
        $screen2 = Screen::factory()->create();

        $bundle = Bundle::factory()->create();

        $bundle->addAsset($screen1);
        $bundle->addAsset($screen2);

        $response = $this->apiCall('POST', route('api.devlink.add-asset', ['bundle' => $bundle->id]), [
            'id' => $screen2->id,
            'type' => $screen2::class,
        ]);

        // assert an error is returned about screen2 already being in the bundle
        $response->assertStatus(422);
        $this->assertEquals('Asset already exists in bundle', $response->json()['error']['message']);
    }

    public function testInstallRemoteAsset()
    {
        $screen = Screen::factory()->create();
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
        ]);

        Http::fake([
            'remote-instance.test/*' => function ($request) use ($screen) {
                $httpRequest = new \Illuminate\Http\Request();
                $httpRequest->replace($request->data());

                $response = (new DevLinkController)->exportLocalAsset($httpRequest);

                // Modify to ensure it gets installed, since we are on the same instance
                $response['export'][$screen->uuid]['attributes']['title'] = 'Modified title';

                return Http::response($response, 200);
            },
        ]);

        $response = $this->apiCall(
            'POST',
            route(
                'api.devlink.install-remote-asset',
                ['devLink' => $devLink->id],
            ),
            ['id' => $screen->id, 'class' => $screen::class]
        );

        $this->assertEquals('queued', $response->json()['status']);

        $screen->refresh();
        $this->assertEquals('Modified title', $screen->title);
    }
}
