<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Http\Controllers\Api\DevLinkController;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\DevLink;
use ProcessMaker\Models\Screen;
use ProcessMaker\Package\PackageDynamicUI\Models\Dashboard;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
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
