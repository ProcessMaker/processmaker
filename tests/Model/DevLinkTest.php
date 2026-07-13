<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    private const REMOTE_INSTANCE_URL = 'https://remote-instance.test';

    private const TEST_BUNDLE_NAME = 'Test Bundle';

    private const TEST_BUNDLE_DESCRIPTION = 'Test Bundle Description';

    private const FIRST_MENU_DESCRIPTION = 'First menu description';

    private const SECOND_MENU_DESCRIPTION = 'Second menu description';

    public function testGetClientUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
        ]);

        $expectedQueryString = http_build_query([
            'devlink_id' => $devLink->id,
            'redirect_uri' => route('devlink.index'),
        ]);

        $this->assertEquals(
            self::REMOTE_INSTANCE_URL . '/admin/devlink/oauth-client?' . $expectedQueryString,
            $devLink->getClientUrl()
        );
    }

    public function testGetOauthRedirectUrl()
    {
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
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
            self::remoteApiUrl('local-bundles/123') => Http::response(self::remoteBundleResponse('5')),
            self::remoteApiUrl('export-local-bundle/123') => Http::response([
                'payloads' => $exports,
            ]),
            self::remoteApiUrl('export-local-bundle/123/settings') => Http::response([
                'settings' => [[
                    'setting' => 'users',
                    'config' => null,
                ]],
            ]),
            self::remoteApiUrl('export-local-bundle/123/settings-payloads') => Http::response([
                'payloads' => $exportsSettingsPayloads,
            ]),
            self::remoteApiUrl('local-bundles/123/add-bundle-instance') => Http::response([], 200),
        ]);

        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
        ]);
        $devLink->installRemoteBundle(123, 'update');

        $bundle = Bundle::where('remote_id', 123)->first();
        $this->assertEquals(self::TEST_BUNDLE_NAME, $bundle->name);
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

    public function testInstallRemoteBundleImportsAndReinstallsEverySelectedMenu()
    {
        if (!hasPackage('package-dynamic-ui')) {
            $this->markTestSkipped('package-dynamic-ui is not installed');
        }

        Storage::fake('local');

        $firstMenu = Menu::factory()->create([
            'name' => 'First DevLink Menu',
            'description' => self::FIRST_MENU_DESCRIPTION,
        ]);
        $secondMenu = Menu::factory()->create([
            'name' => 'Second DevLink Menu',
            'description' => self::SECOND_MENU_DESCRIPTION,
        ]);
        $excludedMenu = Menu::factory()->create([
            'name' => 'Excluded DevLink Menu',
            'description' => 'Excluded menu description',
        ]);

        $sourceBundle = Bundle::factory()->create();
        $sourceBundle->addSettings('ui_menus', null);

        $allMenuRoots = array_column($sourceBundle->exportSettingPayloads()->toArray()[0], 'root');
        $this->assertContains($firstMenu->uuid, $allMenuRoots);
        $this->assertContains($secondMenu->uuid, $allMenuRoots);
        $this->assertContains($excludedMenu->uuid, $allMenuRoots);

        $sourceBundle->addSettings(
            'ui_menus',
            json_encode(['id' => [$firstMenu->id, $secondMenu->id]]),
            null,
            true
        );

        $settings = $sourceBundle->exportSettings();
        $settingPayloads = $sourceBundle->exportSettingPayloads()->toArray();
        $selectedMenuRoots = array_column($settingPayloads[0], 'root');

        $this->assertEqualsCanonicalizing(
            [$firstMenu->uuid, $secondMenu->uuid],
            $selectedMenuRoots
        );
        $this->assertNotContains($excludedMenu->uuid, $selectedMenuRoots);

        $firstMenuUuid = $firstMenu->uuid;
        $secondMenuUuid = $secondMenu->uuid;
        $excludedMenuUuid = $excludedMenu->uuid;
        $firstMenu->delete();
        $secondMenu->delete();
        $sourceBundle->delete();

        Http::fake([
            self::remoteApiUrl('local-bundles/456') => Http::response([
                'id' => 456,
                'name' => 'Menus Bundle',
                'published' => true,
                'version' => '1',
                'description' => 'Bundle with selected menus',
            ]),
            self::remoteApiUrl('export-local-bundle/456') => Http::response([
                'payloads' => [],
            ]),
            self::remoteApiUrl('export-local-bundle/456/settings') => Http::response([
                'settings' => $settings,
            ]),
            self::remoteApiUrl('export-local-bundle/456/settings-payloads') => Http::response([
                'payloads' => $settingPayloads,
            ]),
            self::remoteApiUrl('local-bundles/456/add-bundle-instance') => Http::response([], 200),
        ]);

        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
        ]);
        $devLink->installRemoteBundle(456, 'update');

        $importedFirstMenu = Menu::where('uuid', $firstMenuUuid)->firstOrFail();
        $importedSecondMenu = Menu::where('uuid', $secondMenuUuid)->firstOrFail();
        $this->assertSame(self::FIRST_MENU_DESCRIPTION, $importedFirstMenu->description);
        $this->assertSame(self::SECOND_MENU_DESCRIPTION, $importedSecondMenu->description);
        $this->assertSame(1, Menu::where('uuid', $excludedMenuUuid)->count());

        $installedBundle = Bundle::where('remote_id', 456)->firstOrFail();
        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'partial',
            'available' => true,
            'items' => [
                ['key' => $firstMenuUuid, 'name' => 'First DevLink Menu'],
                ['key' => $secondMenuUuid, 'name' => 'Second DevLink Menu'],
            ],
        ], $installedBundle->settingPreview('ui_menus'));

        $savedPayloads = json_decode(
            gzdecode(file_get_contents($installedBundle->newestVersionFile()->getPath())),
            true
        );
        $savedRoots = array_column($savedPayloads, 'root');
        $this->assertEqualsCanonicalizing([$firstMenuUuid, $secondMenuUuid], $savedRoots);
        $this->assertNotContains($excludedMenuUuid, $savedRoots);

        $importedFirstMenu->update(['description' => 'Locally changed first menu']);
        $importedSecondMenu->update(['description' => 'Locally changed second menu']);

        $installedBundle->reinstall('update');

        $this->assertSame(
            self::FIRST_MENU_DESCRIPTION,
            Menu::where('uuid', $firstMenuUuid)->firstOrFail()->description
        );
        $this->assertSame(
            self::SECOND_MENU_DESCRIPTION,
            Menu::where('uuid', $secondMenuUuid)->firstOrFail()->description
        );
        $this->assertSame(1, Menu::where('uuid', $firstMenuUuid)->count());
        $this->assertSame(1, Menu::where('uuid', $secondMenuUuid)->count());
    }

    public function testRemoteBundles()
    {
        Http::preventStrayRequests();

        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
        ]);

        $existingInstalledRemoteBundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
            'remote_id' => 123,
        ]);

        Http::fake([
            self::remoteApiUrl('local-bundles?published=1') => Http::response([
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
            'url' => self::REMOTE_INSTANCE_URL,
        ]);

        $existingBundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
            'remote_id' => 123,
            'version' => '1',
        ]);

        Http::fake([
            self::remoteApiUrl('local-bundles/123') => Http::sequence()
                ->push(self::remoteBundleResponse('2'), 200)
                ->push(self::remoteBundleResponse('3'), 200)
                ->push(self::remoteBundleResponse('4'), 200)
                ->push(self::remoteBundleResponse('8'), 200)
                ->push(self::remoteBundleResponse('9'), 200),
            self::remoteApiUrl('export-local-bundle/123') => Http::sequence()
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
            self::remoteApiUrl('export-local-bundle/123/settings') => Http::response([
                'settings' => [[
                    'setting' => 'users',
                    'config' => null,
                ]],
            ]),
            self::remoteApiUrl('export-local-bundle/123/settings-payloads') => Http::response([
                'payloads' => [$exportsSettingsPayloads],
            ]),
            self::remoteApiUrl('local-bundles/123/add-bundle-instance') => Http::response([], 200),
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

    private static function remoteApiUrl(string $path): string
    {
        return self::REMOTE_INSTANCE_URL . '/api/1.0/devlink/' . ltrim($path, '/');
    }

    private static function remoteBundleResponse(string $version): array
    {
        return [
            'id' => 123,
            'name' => self::TEST_BUNDLE_NAME,
            'published' => true,
            'version' => $version,
            'description' => self::TEST_BUNDLE_DESCRIPTION,
        ];
    }
}
