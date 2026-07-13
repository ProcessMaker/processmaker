<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class BundleTest extends TestCase
{
    use HelperTrait;

    public function testExport()
    {
        $this->addGlobalSignalProcess();

        $process = Process::factory()->create();
        $screen = Screen::factory()->create();
        $bundle = Bundle::factory()->create();

        BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $process->id,
        ]);

        BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Screen::class,
            'asset_id' => $screen->id,
        ]);

        $payload = $bundle->export();

        $this->assertEquals(2, count($payload));
        $this->assertEquals($process->name, $payload[0]['name']);
        $this->assertEquals($screen->title, $payload[1]['name']);
    }

    public function testSyncAssets()
    {
        $screen1 = Screen::factory()->create(['title' => 'Screen 1']);
        $screen2 = Screen::factory()->create(['title' => 'Screen 2']);
        $screen3 = Screen::factory()->create(['title' => 'Screen 3']);
        $bundle = Bundle::factory()->create();

        $bundle->syncAssets([$screen1, $screen2]);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals($screen1->id, $bundle->assets[0]->asset_id);
        $this->assertEquals($screen2->id, $bundle->assets[1]->asset_id);

        $bundle->syncAssets([$screen1, $screen3]);

        $this->assertCount(2, $bundle->assets);
        $this->assertEquals($screen1->id, $bundle->assets[0]->asset_id);
        $this->assertEquals($screen3->id, $bundle->assets[1]->asset_id);
    }

    public function testReinstallBundle()
    {
        // Remote
        $screen = Screen::factory()->create(['title' => 'Original Screen Name']);
        $screenUuid = $screen->uuid;
        $bundle = Bundle::factory()->create();
        $bundle->syncAssets([$screen]);
        $payloads = $bundle->export();

        $bundle->delete();
        $screen->delete();

        // Local
        $bundle = Bundle::factory()->create();
        $bundle->install($payloads, 'update');
        $bundle->savePayloadsToFile($payloads, [0 => []], null);

        $screen = Screen::where('uuid', $screenUuid)->firstOrFail();
        $screen->title = 'New Screen Name';
        $screen->save();

        $this->assertEquals('New Screen Name', $screen->refresh()->title);
        $bundle->reinstall('update');
        $this->assertEquals('Original Screen Name', $screen->refresh()->title);
    }

    public function testReinstallPassesImportingUserIdToExporter()
    {
        $screen = Screen::factory()->create();
        $screenUuid = $screen->uuid;
        $remoteBundle = Bundle::factory()->create();
        $remoteBundle->syncAssets([$screen]);
        $payloads = $remoteBundle->export();
        $payloads[0]['export'][$screenUuid]['exporter'] = ReinstallContextScreenExporter::class;

        $remoteBundle->delete();
        $screen->delete();

        $localBundle = Bundle::factory()->create();
        $localBundle->install($payloads, 'update');
        $localBundle->savePayloadsToFile($payloads, [0 => []], null);

        $importingUser = User::factory()->create();
        ReinstallContextScreenExporter::$receivedImportingUserId = null;

        $localBundle->reinstall('update', new Logger($importingUser->id));

        $this->assertSame(
            $importingUser->id,
            ReinstallContextScreenExporter::$receivedImportingUserId
        );
    }

    public function testSettingPreviewUsesInstalledPayloadMetadata()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $bundle->addSettings('ui_dashboards', json_encode(['id' => [9001, 9002]]));
        $bundle->addSettings('ui_menus', null);
        $bundle->savePayloadsToFile([], [[
            self::settingPayload('dashboard_package', 'dashboard-zulu', 'Zulu Dashboard'),
            self::settingPayload('menu_package', 'menu-bravo', 'Bravo Menu'),
            self::settingPayload('dashboard_package', 'dashboard-alpha', 'Alpha Dashboard'),
            self::settingPayload('menu_package', 'menu-alpha', 'Alpha Menu'),
        ]]);

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'partial',
            'available' => true,
            'items' => [
                ['key' => 'dashboard-alpha', 'name' => 'Alpha Dashboard'],
                ['key' => 'dashboard-zulu', 'name' => 'Zulu Dashboard'],
            ],
        ], $bundle->settingPreview('ui_dashboards'));

        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'all',
            'available' => true,
            'items' => [
                ['key' => 'menu-alpha', 'name' => 'Alpha Menu'],
                ['key' => 'menu-bravo', 'name' => 'Bravo Menu'],
            ],
        ], $bundle->settingPreview('ui_menus'));
    }

    public function testSettingPreviewReturnsNoneWhenSettingIsNotShared()
    {
        $bundle = Bundle::factory()->create();

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'none',
            'available' => true,
            'items' => [],
        ], $bundle->settingPreview('ui_dashboards'));
    }

    public function testSettingPreviewIsUnavailableWithoutAValidSnapshot()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundleWithoutSnapshot = Bundle::factory()->create();
        $bundleWithoutSnapshot->addSettings('ui_dashboards', null);

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'all',
            'available' => false,
            'items' => [],
        ], $bundleWithoutSnapshot->settingPreview('ui_dashboards'));

        $bundleWithInvalidSnapshot = Bundle::factory()->create(['version' => 1]);
        $bundleWithInvalidSnapshot->addSettings('ui_menus', json_encode(['id' => [9001]]));
        $bundleWithInvalidSnapshot->addMediaFromString(gzencode('{invalid json'))
            ->usingFileName('payloads.json.gz')
            ->withCustomProperties(['version' => $bundleWithInvalidSnapshot->version])
            ->toMediaCollection();

        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'partial',
            'available' => false,
            'items' => [],
        ], $bundleWithInvalidSnapshot->settingPreview('ui_menus'));
    }

    private static function settingPayload(string $type, string $key, string $name): array
    {
        return [
            'type' => $type,
            'version' => '2',
            'root' => $key,
            'name' => $name,
            'export' => [],
        ];
    }
}

class ReinstallContextScreenExporter extends ScreenExporter
{
    public static ?int $receivedImportingUserId = null;

    public function import(): bool
    {
        self::$receivedImportingUserId = $this->options->importingUserId;

        return parent::import();
    }
}
