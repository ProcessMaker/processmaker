<?php

namespace Tests\Model;

use Illuminate\Support\Facades\Storage;
use ProcessMaker\Exception\BundleIntegrityException;
use ProcessMaker\ImportExport\Exporters\ScreenExporter;
use ProcessMaker\ImportExport\Logger;
use ProcessMaker\Models\Bundle;
use ProcessMaker\Models\BundleAsset;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Services\DevLink\BundleFingerprint;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\TestCase;

class BundleTest extends TestCase
{
    use HelperTrait;

    private const ALPHA_DASHBOARD_NAME = 'Alpha Dashboard';

    private const ALPHA_MENU_NAME = 'Alpha Menu';

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

    public function testPublicationFingerprintIsStableAcrossAssetOrder()
    {
        $screen1 = Screen::factory()->create(['title' => 'Screen 1']);
        $screen2 = Screen::factory()->create(['title' => 'Screen 2']);
        $bundle1 = Bundle::factory()->create();
        $bundle2 = Bundle::factory()->create();

        $bundle1->syncAssets([$screen1, $screen2]);
        $bundle2->syncAssets([$screen2, $screen1]);

        $fingerprint = app(BundleFingerprint::class);

        $this->assertSame(
            $fingerprint->calculate($bundle1->fresh()),
            $fingerprint->calculate($bundle2->fresh()),
        );
    }

    public function testPublicationFingerprintIgnoresTimestampsButDetectsAssetChanges()
    {
        $screen = Screen::factory()->create(['title' => 'Original Screen']);
        $bundle = Bundle::factory()->create();
        $bundle->syncAssets([$screen]);
        $fingerprint = app(BundleFingerprint::class);
        $originalFingerprint = $fingerprint->calculate($bundle->fresh());

        $screen->timestamps = false;
        $screen->updated_at = now()->addHour();
        $screen->saveQuietly();
        $screen->timestamps = true;

        $this->assertSame($originalFingerprint, $fingerprint->calculate($bundle->fresh()));

        $screen->title = 'Updated Screen';
        $screen->save();

        $this->assertNotSame($originalFingerprint, $fingerprint->calculate($bundle->fresh()));
    }

    public function testPublicationFingerprintIgnoresBundleMetadata()
    {
        $bundle = Bundle::factory()->create([
            'name' => 'Original Bundle',
            'description' => 'Original Description',
            'published' => true,
        ]);
        $fingerprint = app(BundleFingerprint::class);
        $originalFingerprint = $fingerprint->calculate($bundle);

        $bundle->name = 'Renamed Bundle';
        $bundle->description = 'Updated Description';
        $bundle->published = false;
        $bundle->save();

        $this->assertSame($originalFingerprint, $fingerprint->calculate($bundle->fresh()));
    }

    public function testPublicationFingerprintNormalizesSettingSelectionOrder()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $bundle1 = Bundle::factory()->create();
        $bundle2 = Bundle::factory()->create();

        $bundle1->addSettings('users', json_encode(['id' => [$user1->id, $user2->id]]));
        $bundle2->addSettings('users', json_encode(['id' => [$user2->id, $user1->id]]));

        $fingerprint = app(BundleFingerprint::class);
        $originalFingerprint = $fingerprint->calculate($bundle1->fresh());

        $this->assertSame($originalFingerprint, $fingerprint->calculate($bundle2->fresh()));

        $bundle1->addSettings('users', json_encode(['id' => [$user1->id]]), replaceIds: true);

        $this->assertNotSame($originalFingerprint, $fingerprint->calculate($bundle1->fresh()));
    }

    public function testExportRejectsBundleWithUnavailableAssetsBeforeExporting()
    {
        $bundle = Bundle::factory()->create(['name' => 'Corrupt Bundle']);
        $screen = Screen::factory()->create();
        $missingProcessId = Process::max('id') + 1000;
        BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Screen::class,
            'asset_id' => $screen->id,
        ]);
        $missingBundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => Process::class,
            'asset_id' => $missingProcessId,
        ]);
        $unavailableBundleAsset = BundleAsset::factory()->create([
            'bundle_id' => $bundle->id,
            'asset_type' => 'ProcessMaker\Missing\Asset',
            'asset_id' => 1234,
        ]);

        try {
            $bundle->export();
            $this->fail('Expected bundle integrity validation to fail.');
        } catch (BundleIntegrityException $exception) {
            $this->assertSame(
                'The bundle Corrupt Bundle contains unavailable assets and cannot be exported.',
                $exception->getMessage()
            );
            $this->assertSame([
                [
                    'bundle_asset_id' => $missingBundleAsset->id,
                    'asset_type' => Process::class,
                    'asset_id' => $missingProcessId,
                    'integrity_status' => BundleAsset::INTEGRITY_MISSING,
                ],
                [
                    'bundle_asset_id' => $unavailableBundleAsset->id,
                    'asset_type' => 'ProcessMaker\Missing\Asset',
                    'asset_id' => 1234,
                    'integrity_status' => BundleAsset::INTEGRITY_TYPE_UNAVAILABLE,
                ],
            ], $exception->invalidAssets());
        }
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
        $media = $bundle->newestVersionFile();
        $media->forgetCustomProperty('settings_payloads_complete');
        $media->save();

        $this->assertNull($media->refresh()->getCustomProperty('settings_payloads_complete'));

        $screen = Screen::where('uuid', $screenUuid)->firstOrFail();
        $screen->title = 'New Screen Name';
        $screen->save();

        $this->assertEquals('New Screen Name', $screen->refresh()->title);
        $bundle->reinstall('update');
        $this->assertEquals('Original Screen Name', $screen->refresh()->title);
    }

    public function testReinstallUsesNewestSnapshotWhenVersionsMatch()
    {
        Storage::fake(config('media-library.disk_name'));

        $screen = Screen::factory()->create(['title' => 'First Snapshot']);
        $screenUuid = $screen->uuid;
        $remoteBundle = Bundle::factory()->create(['version' => 1]);
        $remoteBundle->syncAssets([$screen]);
        $firstPayloads = $remoteBundle->export();

        $screen->title = 'Second Snapshot';
        $screen->save();
        $remoteBundle = $remoteBundle->fresh();
        $secondPayloads = $remoteBundle->export();

        $remoteBundle->delete();
        $screen->delete();

        $localBundle = Bundle::factory()->create(['version' => 1]);
        $localBundle->install($firstPayloads, 'update');
        $localBundle->savePayloadsToFile($firstPayloads, []);

        $localBundle = $localBundle->fresh();
        $localBundle->savePayloadsToFile($secondPayloads, []);

        $screen = Screen::where('uuid', $screenUuid)->firstOrFail();
        $screen->title = 'Local Change';
        $screen->save();

        $localBundle->reinstall('update');

        $this->assertSame('Second Snapshot', $screen->refresh()->title);
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
            self::settingPayload('dashboard_package', 'dashboard-alpha', self::ALPHA_DASHBOARD_NAME),
            self::settingPayload('menu_package', 'menu-alpha', self::ALPHA_MENU_NAME),
        ]]);

        $this->assertTrue($bundle->newestVersionFile()->getCustomProperty('settings_payloads_complete'));

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'partial',
            'available' => true,
            'items' => [
                ['key' => 'dashboard-alpha', 'name' => self::ALPHA_DASHBOARD_NAME],
                ['key' => 'dashboard-zulu', 'name' => 'Zulu Dashboard'],
            ],
        ], $bundle->settingPreview('ui_dashboards'));

        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'all',
            'available' => true,
            'items' => [
                ['key' => 'menu-alpha', 'name' => self::ALPHA_MENU_NAME],
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

    public function testSettingPreviewIsUnavailableForUnmarkedLegacySnapshot()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $bundle->addSettings('ui_dashboards', null);
        $bundle->addSettings('ui_menus', json_encode(['id' => [9001]]));
        $bundle->addMediaFromString(gzencode(json_encode([
            self::settingPayload('dashboard_package', 'dashboard-alpha', self::ALPHA_DASHBOARD_NAME),
            self::settingPayload('menu_package', 'menu-alpha', self::ALPHA_MENU_NAME),
        ])))
            ->usingFileName('payloads.json.gz')
            ->withCustomProperties(['version' => $bundle->version])
            ->toMediaCollection();

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'all',
            'available' => false,
            'items' => [],
        ], $bundle->settingPreview('ui_dashboards'));

        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'partial',
            'available' => false,
            'items' => [],
        ], $bundle->settingPreview('ui_menus'));
    }

    public function testSettingPreviewUsesNewestCompleteSnapshotForSameVersion()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $bundle->addSettings('ui_dashboards', null);
        $legacyMedia = $bundle->addMediaFromString(gzencode(json_encode([
            self::settingPayload('dashboard_package', 'dashboard-legacy', 'Legacy Dashboard'),
        ])))
            ->usingFileName('payloads.json.gz')
            ->withCustomProperties(['version' => $bundle->version])
            ->toMediaCollection();

        $bundle->savePayloadsToFile([
            self::settingPayload('dashboard_package', 'dashboard-current', 'Current Dashboard'),
        ], []);

        $completeMedia = $bundle->fresh()->getMedia()->sortByDesc('id')->first();

        $this->assertGreaterThan($legacyMedia->id, $completeMedia->id);
        $this->assertSame($completeMedia->id, $bundle->fresh()->newestVersionFile()->id);
        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'all',
            'available' => true,
            'items' => [
                ['key' => 'dashboard-current', 'name' => 'Current Dashboard'],
            ],
        ], $bundle->fresh()->settingPreview('ui_dashboards'));
    }

    public function testSavingPayloadsKeepsNewestThreeSnapshotsForSameVersion()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $createdMediaIds = [];

        for ($snapshot = 1; $snapshot <= 5; $snapshot++) {
            $bundle = $bundle->fresh();
            $bundle->savePayloadsToFile([
                self::settingPayload(
                    'dashboard_package',
                    "dashboard-$snapshot",
                    "Dashboard $snapshot"
                ),
            ], []);
            $createdMediaIds[] = $bundle->fresh()->getMedia()->max('id');
        }

        $bundle = $bundle->fresh();
        $expectedMediaIds = array_reverse(array_slice($createdMediaIds, -3));

        $this->assertCount(3, $bundle->getMedia());
        $this->assertSame($expectedMediaIds, $bundle->filesSortedByVersion()->pluck('id')->all());
        $this->assertSame($expectedMediaIds[0], $bundle->newestVersionFile()->id);
    }

    public function testSettingPreviewReturnsAvailableEmptyListForCompleteSnapshot()
    {
        Storage::fake(config('media-library.disk_name'));

        $bundle = Bundle::factory()->create(['version' => 1]);
        $bundle->addSettings('ui_dashboards', null);
        $bundle->addSettings('ui_menus', null);
        $bundle->savePayloadsToFile([], []);

        $this->assertSame([
            'setting' => 'ui_dashboards',
            'selection' => 'all',
            'available' => true,
            'items' => [],
        ], $bundle->settingPreview('ui_dashboards'));

        $this->assertSame([
            'setting' => 'ui_menus',
            'selection' => 'all',
            'available' => true,
            'items' => [],
        ], $bundle->settingPreview('ui_menus'));
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
            ->withCustomProperties([
                'version' => $bundleWithInvalidSnapshot->version,
                'settings_payloads_complete' => true,
            ])
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
