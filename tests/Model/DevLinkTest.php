<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
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
        Storage::fake('local');

        $screen1 = Screen::factory()->create(['title' => 'Screen 1']);
        $screen2 = Screen::factory()->create(['title' => 'Screen 2']);

        $user1 = User::factory()->create();

        $bundle = Bundle::factory()->create([]);
        $bundle->syncAssets([$screen1, $screen2]);
        $bundle->addSettings('users', $user1->id);
        $exports = $bundle->export();
        $exportsSettingsPayloads = $bundle->exportSettingPayloads();

        $screen1->delete();
        $screen2->delete();
        $bundle->delete();

        Http::fake([
            'http://remote-instance.test/api/1.0/devlink/local-bundles/123' => Http::response([
                'id' => 123,
                'name' => 'Test Bundle',
                'published' => true,
                'version' => '5',
                'description' => 'Test Bundle Description',
            ]),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123' => Http::response([
                'payloads' => $exports,
            ]),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123/settings' => Http::response([
                'settings' => [[
                    'setting' => 'users',
                    'config' => null,
                ]],
            ]),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123/settings-payloads' => Http::response([
                'payloads' => $exportsSettingsPayloads,
            ]),
            'http://remote-instance.test/api/1.0/devlink/local-bundles/123/add-bundle-instance' => Http::response([], 200),
        ]);

        $devLink = DevLink::factory()->create([
            'url' => 'http://remote-instance.test',
        ]);
        $devLink->installRemoteBundle(123, 'update');

        $bundle = Bundle::where('remote_id', 123)->first();
        $this->assertEquals('Test Bundle', $bundle->name);
        $this->assertEquals('5', $bundle->version);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals('Screen 1', $bundle->assets[0]->asset->title);
        $this->assertEquals('Screen 2', $bundle->assets[1]->asset->title);

        // test that we saved the payload
        $media = $bundle->getMedia();
        $this->assertCount(1, $media);
        $gzPath = $media[0]->getPath();
        $payloads = json_decode(gzdecode(file_get_contents($gzPath)), true);
        $this->assertCount(3, $payloads);
    }

    public function testRemoteBundles()
    {
        Http::preventStrayRequests();

        $devLink = DevLink::factory()->create([
            'url' => 'http://remote-instance.test',
        ]);

        $existingInstalledRemoteBundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
            'remote_id' => 123,
        ]);

        Http::fake([
            'http://remote-instance.test/api/1.0/devlink/local-bundles?published=1' => Http::response([
                'data' => [
                    [
                        'id' => $existingInstalledRemoteBundle->remote_id,
                    ],
                    [
                        'id' => 456,
                    ],
                ],
            ]),
        ]);

        $bundles = $devLink->remoteBundles(null);
        $this->assertCount(2, $bundles['data']);
        $this->assertEquals($bundles['data'][0]['is_installed'], true);
        $this->assertEquals($bundles['data'][1]['is_installed'], false);
    }

    public function testUpdateBundle()
    {
        Storage::fake('local');

        // Remote Instance
        $screen = Screen::factory()->create(['title' => 'Screen Name']);
        $bundle = Bundle::factory()->create([]);
        $user1 = User::factory()->create();
        $bundle->addSettings('users', $user1->id);
        $bundle->syncAssets([$screen]);
        $exports = $bundle->export();
        $exportsSettingsPayloads = $bundle->exportSettingPayloads();

        $screenUuid = $screen->uuid;

        $screen->delete();
        $bundle->delete();

        $exportsNewScreenName = $exports;
        $exportsNewScreenName[0]['export'][$screen->uuid]['attributes']['title'] = 'Screen Name Updated';

        // Local Instance
        $devLink = DevLink::factory()->create([
            'url' => 'http://remote-instance.test',
        ]);

        $existingBundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
            'remote_id' => 123,
            'version' => '1',
        ]);

        Http::fake([
            'http://remote-instance.test/api/1.0/devlink/local-bundles/123' => Http::sequence()
                ->push([
                    'id' => 123,
                    'name' => 'Test Bundle',
                    'published' => true,
                    'version' => '2',
                    'description' => 'Test Bundle Description',
                ], 200)
                ->push([
                    'id' => 123,
                    'name' => 'Test Bundle',
                    'published' => true,
                    'version' => '3',
                    'description' => 'Test Bundle Description',
                ], 200)
                ->push([
                    'id' => 123,
                    'name' => 'Test Bundle',
                    'published' => true,
                    'version' => '4',
                    'description' => 'Test Bundle Description',
                ], 200)
                ->push([
                    'id' => 123,
                    'name' => 'Test Bundle',
                    'published' => true,
                    'version' => '8',
                    'description' => 'Test Bundle Description',
                ], 200)
                ->push([
                    'id' => 123,
                    'name' => 'Test Bundle',
                    'published' => true,
                    'version' => '9',
                    'description' => 'Test Bundle Description',
                ], 200),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123' => Http::sequence()
                ->push([
                    'payloads' => $exports,
                ], 200)
                ->push([
                    'payloads' => $exportsNewScreenName,
                ], 200)
                ->push([
                    'payloads' => $exportsNewScreenName,
                ], 200)
                ->push([
                    'payloads' => $exportsNewScreenName,
                ], 200)
                ->push([
                    'payloads' => $exportsNewScreenName,
                ], 200),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123/settings' => Http::response([
                'settings' => [[
                    'setting' => 'users',
                    'config' => null,
                ]],
            ]),
            'http://remote-instance.test/api/1.0/devlink/export-local-bundle/123/settings-payloads' => Http::response([
                'payloads' => [$exportsSettingsPayloads],
            ]),
            'http://remote-instance.test/api/1.0/devlink/local-bundles/123/add-bundle-instance' => Http::response([], 200),
        ]);

        $devLink->installRemoteBundle(123, 'update');
        $screen = Screen::where('uuid', $screenUuid)->first();
        $this->assertEquals('Screen Name', $screen->title);

        $devLink->installRemoteBundle(123, 'update');
        $screen->refresh();
        $this->assertEquals('Screen Name Updated', $screen->title);

        // Check saved media
        $media = $existingBundle->getMedia();
        $this->assertCount(2, $media);
        $this->assertEquals($media[0]->getCustomProperty('version'), '2');
        $this->assertEquals($media[1]->getCustomProperty('version'), '3');

        // only the latest 3 versions should be saved
        $devLink->installRemoteBundle(123, 'update');
        $devLink->installRemoteBundle(123, 'update');
        $devLink->installRemoteBundle(123, 'update');

        $media = $existingBundle->refresh()->getMedia();
        $this->assertCount(3, $media);
        $savedVersions = $media->map(fn ($m) => $m->getCustomProperty('version'))->toArray();
        $this->assertEquals(['4', '8', '9'], $savedVersions);
    }
}
