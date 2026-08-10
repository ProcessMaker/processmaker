<?php

namespace Tests\Feature\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Jobs\DevLinkInstall;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\PackageDynamicUI\Models\Dashboard;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DevLinkTest extends TestCase
{
    use RequestHelper;

    private const REMOTE_QA_NAME = 'Remote QA';

    private const REMOTE_INSTANCE_URL = 'https://remote-instance.test';

    private const EXISTING_LINK_NAME = 'Existing Link';

    private const FIRST_LINK_NAME = 'First Link';

    private const SECOND_LINK_NAME = 'Second Link';

    private const FIRST_INSTANCE_URL = 'https://first-instance.test';

    private const SECOND_INSTANCE_URL = 'https://second-instance.test';

    public function testStoreCreatesADevLinkWithANormalizedUrl()
    {
        $response = $this->apiCall('POST', route('api.devlink.store'), [
            'name' => self::REMOTE_QA_NAME,
            'url' => ' https://REMOTE-INSTANCE.test:443/ ',
        ]);

        $response->assertCreated()->assertJson([
            'name' => self::REMOTE_QA_NAME,
            'url' => self::REMOTE_INSTANCE_URL,
        ]);
        $this->assertDatabaseHas('dev_links', [
            'name' => self::REMOTE_QA_NAME,
            'url' => self::REMOTE_INSTANCE_URL,
        ]);
    }

    #[DataProvider('duplicateUrlProvider')]
    public function testStoreRejectsNormalizedDuplicateUrls(string $storedUrl, string $duplicateUrl)
    {
        DevLink::factory()->create([
            'name' => self::EXISTING_LINK_NAME,
            'url' => $storedUrl,
        ]);

        $response = $this->apiCall('POST', route('api.devlink.store'), [
            'name' => 'Duplicate Link',
            'url' => $duplicateUrl,
        ]);

        $response->assertUnprocessable()->assertJsonPath(
            'errors.url.0',
            'This instance is already linked as Existing Link. Open or reconnect the existing connection.'
        );
        $this->assertDatabaseCount('dev_links', 1);
    }

    public static function duplicateUrlProvider(): array
    {
        return [
            'host case and trailing slash' => [
                self::REMOTE_INSTANCE_URL,
                'https://REMOTE-INSTANCE.test/',
            ],
            'default HTTPS port' => [
                self::REMOTE_INSTANCE_URL,
                'https://remote-instance.test:443',
            ],
            'default HTTP port' => [
                'http://remote-instance.test',
                'http://remote-instance.test:80/',
            ],
        ];
    }

    #[DataProvider('nonOriginUrlProvider')]
    public function testStoreRejectsUrlsThatAreNotHttpOrigins(string $url)
    {
        $response = $this->apiCall('POST', route('api.devlink.store'), [
            'name' => self::REMOTE_QA_NAME,
            'url' => $url,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('url');
        $this->assertDatabaseCount('dev_links', 0);
    }

    public static function nonOriginUrlProvider(): array
    {
        return [
            'path' => [self::REMOTE_INSTANCE_URL . '/api'],
            'query' => [self::REMOTE_INSTANCE_URL . '?x=1'],
            'fragment' => [self::REMOTE_INSTANCE_URL . '#section'],
            'username' => ['https://user@remote-instance.test'],
            'username and password' => ['https://user:password@remote-instance.test'],
        ];
    }

    public function testStoreRejectsAnExistingNameWithoutUpdatingItsUrl()
    {
        $devLink = DevLink::factory()->create([
            'name' => self::EXISTING_LINK_NAME,
            'url' => self::FIRST_INSTANCE_URL,
        ]);

        $response = $this->apiCall('POST', route('api.devlink.store'), [
            'name' => self::EXISTING_LINK_NAME,
            'url' => self::SECOND_INSTANCE_URL,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame(self::FIRST_INSTANCE_URL, $devLink->fresh()->url);
        $this->assertDatabaseMissing('dev_links', ['url' => self::SECOND_INSTANCE_URL]);
    }

    public function testStoreAllowsDifferentNormalizedUrls()
    {
        DevLink::factory()->create([
            'name' => self::FIRST_LINK_NAME,
            'url' => self::FIRST_INSTANCE_URL,
        ]);

        $response = $this->apiCall('POST', route('api.devlink.store'), [
            'name' => self::SECOND_LINK_NAME,
            'url' => self::SECOND_INSTANCE_URL,
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('dev_links', 2);
    }

    public function testUpdateRejectsANameUsedByAnotherDevLink()
    {
        DevLink::factory()->create([
            'name' => self::FIRST_LINK_NAME,
            'url' => self::FIRST_INSTANCE_URL,
        ]);
        $secondDevLink = DevLink::factory()->create([
            'name' => self::SECOND_LINK_NAME,
            'url' => self::SECOND_INSTANCE_URL,
        ]);

        $response = $this->apiCall('PUT', route('api.devlink.update', [
            'devLink' => $secondDevLink->id,
        ]), [
            'name' => self::FIRST_LINK_NAME,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame(self::SECOND_LINK_NAME, $secondDevLink->fresh()->name);
    }

    public function testRemoteBundleVersionReportsAnAvailableRemoteBundle()
    {
        Http::preventStrayRequests();
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
            'access_token' => 'valid-token',
        ]);
        Http::fake([
            'https://remote-instance.test/api/1.0/devlink/local-bundles/123' => Http::response([
                'id' => 123,
                'name' => 'Remote Bundle',
                'version' => '4',
            ]),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.remote-version', [
            'devLink' => $devLink->id,
            'bundle' => 123,
        ]));

        $response->assertOk()->assertJson([
            'id' => 123,
            'name' => 'Remote Bundle',
            'version' => '4',
            'available' => true,
        ]);
    }

    #[DataProvider('unavailableRemoteStatusProvider')]
    public function testRemoteBundleVersionHandlesRemoteHttpErrors(int $status)
    {
        Http::preventStrayRequests();
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
            'access_token' => 'revoked-token',
        ]);
        Http::fake([
            'https://remote-instance.test/api/1.0/devlink/local-bundles/123' => Http::response([], $status),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.remote-version', [
            'devLink' => $devLink->id,
            'bundle' => 123,
        ]));

        $response->assertOk()->assertExactJson([
            'available' => false,
            'version' => null,
        ]);
    }

    public static function unavailableRemoteStatusProvider(): array
    {
        return [
            'unauthorized' => [401],
            'forbidden' => [403],
            'missing bundle' => [404],
            'remote server error' => [500],
        ];
    }

    public function testRemoteBundleVersionHandlesConnectionErrors()
    {
        Http::preventStrayRequests();
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
            'access_token' => 'unreachable-token',
        ]);
        Http::fake(function () {
            throw new ConnectionException('Unable to connect to remote instance.');
        });

        $response = $this->apiCall('GET', route('api.devlink.remote-version', [
            'devLink' => $devLink->id,
            'bundle' => 123,
        ]));

        $response->assertOk()->assertExactJson([
            'available' => false,
            'version' => null,
        ]);
    }

    public function testShowBundle()
    {
        $bundle = Bundle::factory()->create();
        $response = $this->apiCall('GET', route('api.devlink.local-bundle', ['bundle' => $bundle->id]));

        $response->assertStatus(200);
        $this->assertEquals($bundle->id, $response->json()['id']);
    }

    public function testShowBundleReportsUnavailableAssetsWithoutFailing()
    {
        $bundle = Bundle::factory()->create();
        $missingProcessId = Process::max('id') + 1000;
        $bundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $missingProcessId,
        ]);

        $response = $this->apiCall('GET', route('api.devlink.local-bundle', ['bundle' => $bundle->id]));

        $response->assertOk()
            ->assertJsonPath('assets.0.id', $bundleAsset->id)
            ->assertJsonPath('assets.0.name', "Missing Process #$missingProcessId")
            ->assertJsonPath('assets.0.url', null)
            ->assertJsonPath('assets.0.integrity_status', BundleAsset::INTEGRITY_MISSING);
    }

    public function testExportBundleRejectsUnavailableAssets()
    {
        $bundle = Bundle::factory()->create(['name' => 'Corrupt Bundle']);
        $missingProcessId = Process::max('id') + 1000;
        $bundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $missingProcessId,
        ]);

        $response = $this->apiCall('GET', route('api.devlink.export-local-bundle', [
            'bundle' => $bundle->id,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 422)
            ->assertJsonPath(
                'error.message',
                'The bundle Corrupt Bundle contains unavailable assets and cannot be exported.'
            )
            ->assertJsonPath('errors.assets.0.bundle_asset_id', $bundleAsset->id)
            ->assertJsonPath('errors.assets.0.asset_type', Process::class)
            ->assertJsonPath('errors.assets.0.asset_id', $missingProcessId)
            ->assertJsonPath('errors.assets.0.integrity_status', BundleAsset::INTEGRITY_MISSING);
    }

    public function testInstalledBundleAllowsRemovingOnlyUnavailableAssets()
    {
        $devLink = DevLink::factory()->create();
        $bundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
            'remote_id' => 123,
        ]);
        $screen = Screen::factory()->create();
        $validBundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Screen::class,
            'asset_id' => $screen->id,
        ]);
        $missingProcessId = Process::max('id') + 1000;
        $invalidBundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $missingProcessId,
        ]);

        $this->apiCall('DELETE', route('api.devlink.delete-bundle-asset', [
            'bundle_asset' => $validBundleAsset->id,
        ]))->assertStatus(422);
        $this->assertDatabaseHas('bundle_assets', ['id' => $validBundleAsset->id]);

        $this->apiCall('DELETE', route('api.devlink.delete-bundle-asset', [
            'bundle_asset' => $invalidBundleAsset->id,
        ]))->assertOk();
        $this->assertDatabaseMissing('bundle_assets', ['id' => $invalidBundleAsset->id]);
    }

    public function testLocalBundlesCanOrderNewestBundlesFirst()
    {
        $oldBundle = Bundle::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        $newBundle = Bundle::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.local-bundles', [
            'order_by' => 'created_at',
            'order_direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $this->assertEquals($newBundle->id, $response->json('data.0.id'));
        $this->assertNotEquals($oldBundle->id, $response->json('data.0.id'));
    }

    public function testLocalBundlesCanReturnOneHundredRecords()
    {
        Bundle::factory()->count(101)->create();

        $response = $this->apiCall('GET', route('api.devlink.local-bundles', [
            'per_page' => 100,
        ]));

        $response->assertStatus(200);
        $this->assertCount(100, $response->json('data'));
        $this->assertEquals(100, $response->json('meta.per_page'));
    }

    public function testLocalBundlesFilterFindsBundleOutsideFirstPage()
    {
        $targetBundle = Bundle::factory()->create([
            'name' => 'FOUR-31727 Search Target',
            'created_at' => now()->subDays(2),
        ]);
        Bundle::factory()->count(15)->create([
            'created_at' => now(),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.local-bundles', [
            'filter' => 'FOUR-31727 Search Target',
        ]));

        $response->assertStatus(200);
        $this->assertEquals($targetBundle->id, $response->json('data.0.id'));
        $this->assertCount(1, $response->json('data'));
    }

    public function testLocalBundlesEditableFilterExcludesRemoteBundles()
    {
        $devLink = DevLink::factory()->create();
        $localBundle = Bundle::factory()->create([
            'dev_link_id' => null,
        ]);
        $remoteBundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
        ]);

        $response = $this->apiCall('GET', route('api.devlink.local-bundles', [
            'editable' => true,
            'per_page' => 100,
        ]));

        $response->assertStatus(200);
        $bundleIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($bundleIds->contains($localBundle->id));
        $this->assertFalse($bundleIds->contains($remoteBundle->id));
    }

    public function testGetBundleAllSettingsReturnsDashboardAndMenuOptions()
    {
        if (!hasPackage('package-dynamic-ui')) {
            $this->markTestSkipped('package-dynamic-ui is not installed');
        }

        $dashboardZeta = Dashboard::factory()->create(['title' => 'ZZ DevLink Dashboard']);
        $dashboardAlpha = Dashboard::factory()->create(['title' => 'AA DevLink Dashboard']);
        $menuZeta = Menu::factory()->create(['name' => 'ZZ DevLink Menu']);
        $menuAlpha = Menu::factory()->create(['name' => 'AA DevLink Menu']);

        $dashboardResponse = $this->apiCall(
            'GET',
            route('api.devlink.local-bundle-all-settings', ['settingKey' => 'ui_dashboards'])
        );
        $dashboardResponse->assertOk();

        $dashboardOptions = collect($dashboardResponse->json())
            ->whereIn('key', [$dashboardAlpha->id, $dashboardZeta->id])
            ->values()
            ->all();
        $this->assertSame([
            ['key' => $dashboardAlpha->id, 'name' => $dashboardAlpha->title],
            ['key' => $dashboardZeta->id, 'name' => $dashboardZeta->title],
        ], $dashboardOptions);

        $menuResponse = $this->apiCall(
            'GET',
            route('api.devlink.local-bundle-all-settings', ['settingKey' => 'ui_menus'])
        );
        $menuResponse->assertOk();

        $menuOptions = collect($menuResponse->json())
            ->whereIn('key', [$menuAlpha->id, $menuZeta->id])
            ->values()
            ->all();
        $this->assertSame([
            ['key' => $menuAlpha->id, 'name' => $menuAlpha->name],
            ['key' => $menuZeta->id, 'name' => $menuZeta->name],
        ], $menuOptions);
    }

    public function testGetBundleSettingPreviewReturnsInstalledSourceMetadata()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $bundle->addSettings('ui_dashboards', json_encode(['id' => [9001, 9002]]));
        $bundle->savePayloadsToFile([], [[
            [
                'type' => 'dashboard_package',
                'version' => '2',
                'root' => 'dashboard-zulu',
                'name' => 'Zulu Dashboard',
                'export' => [],
            ],
            [
                'type' => 'dashboard_package',
                'version' => '2',
                'root' => 'dashboard-alpha',
                'name' => 'Alpha Dashboard',
                'export' => [],
            ],
        ]]);

        $response = $this->apiCall(
            'GET',
            route('api.devlink.local-bundle-setting-preview', [
                'bundle' => $bundle->id,
                'settingKey' => 'ui_dashboards',
            ])
        );

        $response->assertOk()->assertExactJson([
            'setting' => 'ui_dashboards',
            'selection' => 'partial',
            'available' => true,
            'items' => [
                ['key' => 'dashboard-alpha', 'name' => 'Alpha Dashboard'],
                ['key' => 'dashboard-zulu', 'name' => 'Zulu Dashboard'],
            ],
        ]);

        $bundleWithoutSnapshot = Bundle::factory()->create();
        $bundleWithoutSnapshot->addSettings('ui_menus', null);
        $unavailableResponse = $this->apiCall(
            'GET',
            route('api.devlink.local-bundle-setting-preview', [
                'bundle' => $bundleWithoutSnapshot->id,
                'settingKey' => 'ui_menus',
            ])
        );
        $unavailableResponse->assertOk()->assertJson([
            'selection' => 'all',
            'available' => false,
            'items' => [],
        ]);

        $unsupportedResponse = $this->apiCall(
            'GET',
            route('api.devlink.local-bundle-setting-preview', [
                'bundle' => $bundle->id,
                'settingKey' => 'users',
            ])
        );
        $unsupportedResponse->assertNotFound();
    }

    #[DataProvider('selectableUiSettingsProvider')]
    public function testAddSettingsPersistsPartialAllAndEmptySelections(string $settingKey)
    {
        $bundle = Bundle::factory()->create();
        $url = route('api.devlink.add-settings', ['bundle' => $bundle->id]);

        $partialSelection = [41, 42];
        $response = $this->apiCall('POST', $url, [
            'setting' => $settingKey,
            'config' => json_encode(['id' => $partialSelection]),
            'type' => null,
            'replaceIds' => true,
        ]);
        $response->assertOk();

        $bundleSetting = $bundle->settings()->where('setting', $settingKey)->firstOrFail();
        $this->assertSame($partialSelection, json_decode($bundleSetting->config, true)['id']);

        $response = $this->apiCall('POST', $url, [
            'setting' => $settingKey,
            'config' => null,
            'type' => null,
            'replaceIds' => true,
        ]);
        $response->assertOk();
        $this->assertNull($bundleSetting->refresh()->config);

        $response = $this->apiCall('POST', $url, [
            'setting' => $settingKey,
            'config' => json_encode(['id' => []]),
            'type' => null,
            'replaceIds' => true,
        ]);
        $response->assertOk();
        $this->assertDatabaseMissing('bundle_settings', [
            'bundle_id' => $bundle->id,
            'setting' => $settingKey,
        ]);
    }

    public static function selectableUiSettingsProvider(): array
    {
        return [
            'Dashboards' => ['ui_dashboards'],
            'Menus' => ['ui_menus'],
        ];
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

    public function testPingReturnsOkWhenRemotePongSucceeds()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'access_token' => 'token',
        ]);

        Http::fake([
            'remote-instance.test/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.ping', ['devLink' => $devLink->id]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function testPingReturnsAuthorizationRequiredWhenRemotePongReturnsUnauthorized()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'access_token' => 'token',
        ]);

        Http::fake([
            'remote-instance.test/*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.ping', ['devLink' => $devLink->id]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'authorization_required']);
    }

    public function testPingReturnsAuthorizationRequiredWhenRemotePongReturnsForbidden()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'access_token' => 'token',
        ]);

        Http::fake([
            'remote-instance.test/*' => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.ping', ['devLink' => $devLink->id]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'authorization_required']);
    }

    public function testPingReturnsErrorWhenRemotePongFails()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'access_token' => 'token',
        ]);

        Http::fake([
            'remote-instance.test/*' => Http::response(['message' => 'Server error'], 500),
        ]);

        $response = $this->apiCall('GET', route('api.devlink.ping', ['devLink' => $devLink->id]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'error']);
    }

    public function testPingReturnsErrorWhenRemotePongCannotConnect()
    {
        $devLink = DevLink::factory()->create([
            'url' => 'https://remote-instance.test',
            'access_token' => 'token',
        ]);

        Http::fake([
            'remote-instance.test/*' => function () {
                throw new ConnectionException('Connection failed');
            },
        ]);

        $response = $this->apiCall('GET', route('api.devlink.ping', ['devLink' => $devLink->id]));

        $response->assertStatus(200);
        $response->assertJson(['status' => 'error']);
    }

    public function testInstallRemoteAsset()
    {
        $screen = Screen::factory()->create();
        $devLink = DevLink::factory()->create([
            'url' => self::REMOTE_INSTANCE_URL,
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

    public function testInstallEndpointsPassOperationIdToQueuedJobs()
    {
        Bus::fake();

        $operationId = (string) Str::uuid();
        $devLink = DevLink::factory()->create();
        $bundle = Bundle::factory()->create([
            'dev_link_id' => $devLink->id,
        ]);

        $installResponse = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-bundle', [
                'devLink' => $devLink->id,
                'remoteBundleId' => 123,
            ]),
            ['operation_id' => $operationId]
        );
        $reinstallResponse = $this->apiCall(
            'POST',
            route('api.devlink.reinstall-bundle', ['bundle' => $bundle->id]),
            ['operation_id' => $operationId]
        );
        $assetResponse = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-asset', ['devLink' => $devLink->id]),
            [
                'id' => 456,
                'class' => Screen::class,
                'operation_id' => $operationId,
            ]
        );

        $installResponse->assertOk()->assertExactJson(['status' => 'queued']);
        $reinstallResponse->assertOk()->assertExactJson(['status' => 'queued']);
        $assetResponse->assertOk()->assertExactJson(['status' => 'queued']);

        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_INSTALL_BUNDLE
                && $job->operationId === $operationId;
        });
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_REINSTALL_BUNDLE
                && $job->operationId === $operationId;
        });
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) use ($operationId) {
            return $job->type === DevLinkInstall::TYPE_IMPORT_ASSET
                && $job->operationId === $operationId;
        });
    }

    public function testInstallEndpointGeneratesOperationIdWhenMissing()
    {
        Bus::fake();

        $devLink = DevLink::factory()->create();
        $response = $this->apiCall(
            'POST',
            route('api.devlink.install-remote-asset', ['devLink' => $devLink->id]),
            [
                'id' => 456,
                'class' => Screen::class,
            ]
        );

        $response->assertOk()->assertExactJson(['status' => 'queued']);
        Bus::assertDispatched(DevLinkInstall::class, function (DevLinkInstall $job) {
            return Str::isUuid($job->operationId);
        });
    }
}
