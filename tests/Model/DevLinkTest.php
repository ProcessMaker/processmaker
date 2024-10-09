<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Http;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    public function testGetClientUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
        ]);

        $expectedQueryString = http_build_query([
            'devlink_id' => $devLink->id,
            'redirect_uri' => route('devlink.index'),
        ]);

        $this->assertEquals(
            'https://remote-instance.test/admin/devlink/oauth-client?' . $expectedQueryString,
            $devLink->getClientUrl()
        );
    }

    public function testGetOauthRedirectUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'client_id' => 123,
        ]);

        $actualUrl = $devLink->getOauthRedirectUrl();

        // Refresh devlink to get state created by getOauthRedirectUrl
        $devLink->refresh();
        $state = $devLink->state;

        $expectedQueryString = http_build_query([
            'client_id' => 123,
            'redirect_uri' => route('devlink.index'),
            'response_type' => 'code',
            'state' => $state,
        ]);

        $this->assertEquals(
            $devLink->url . '/oauth/authorize?' . $expectedQueryString,
            $actualUrl,
        );
    }

    public function testInstallRemoteBundle()
    {
        $screen1 = Screen::factory()->create(['title' => 'Screen 1']);
        $screen2 = Screen::factory()->create(['title' => 'Screen 2']);
        $bundle = Bundle::factory()->create([]);
        $bundle->syncAssets([$screen1, $screen2]);

        $exports = $bundle->export();

        $screen1->delete();
        $screen2->delete();
        $bundle->delete();

        Http::fake([
            'http://remote-instance.test/api/1.0/devlink/local-bundles/123' => Http::response([
                'id' => 123,
                'name' => 'Test Bundle',
                'published' => true,
                'locked' => false,
                'version' => '5',
            ]),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123' => Http::response([
                'payloads' => $exports,
            ]),
        ]);

        $devLink = DevLink::factory()->create([
            'url' => 'http://remote-instance.test',
        ]);
        $devLink->installRemoteBundle(123);

        $bundle = Bundle::where('remote_id', 123)->first();
        $this->assertEquals('Test Bundle', $bundle->name);
        $this->assertEquals('5', $bundle->version);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals('Screen 1', $bundle->assets[0]->asset->title);
        $this->assertEquals('Screen 2', $bundle->assets[1]->asset->title);
    }
}
