<?php

namespace Tests\Feature\ImportExport\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;
use ZipArchive;

/**
 * Spec for the shared import-file leak.
 *
 * These assertions describe the authorized behavior. They fail while queued
 * import preview, manifest reads, debug download, and queued import all use
 * the single global paths in ImportV2.
 */
class SharedImportFileAuthorizationTest extends TestCase
{
    use RequestHelper;

    public $withPermissions = true;

    private const PACKAGE_PASSWORD = 'placeholder-import-password';

    protected function setUp(): void
    {
        if (!class_exists(\ProcessMaker\Package\Translations\Models\Language::class)) {
            require_once __DIR__ . '/TranslationLanguageStub.php';
        }

        parent::setUp();

        foreach ([
            ImportV2::FILE_PATH,
            ImportV2::OPTIONS_PATH,
            ImportV2::MANIFEST_PATH,
            ImportV2::DEBUG_ZIP_PATH,
            ImportV2::LOG_PATH,
        ] as $path) {
            Storage::delete($path);
        }
    }

    public function testSecondImporterCannotReadQueuedPreviewManifest(): void
    {
        $owner = $this->makeUser(['export-processes', 'import-processes']);
        $otherImporter = $this->makeUser(['import-processes']);

        $marker = 'script-source-marker-owner';
        $envPlaceholder = 'placeholder-environment-value';
        $package = $this->exportEncryptedPackage($owner, $marker, 'ImportLeakProbe', $envPlaceholder);

        $preview = $this->queuePreview($owner, $package);
        $preview->assertOk();
        $this->assertTrue($preview->json('queued'));

        $ownerManifest = $this->actingAs($owner, 'api')
            ->getJson(route('api.import.get-import-manifest'));
        $ownerManifest->assertOk();
        $this->assertManifestContainsPackage($ownerManifest->getContent(), $marker, $envPlaceholder);

        $otherManifest = $this->actingAs($otherImporter, 'api')
            ->getJson(route('api.import.get-import-manifest'));
        $this->assertManifestHidesPackage($otherManifest, $marker, $envPlaceholder);
    }

    public function testQueuedPreviewsStayIsolatedBetweenImporters(): void
    {
        $owner = $this->makeUser(['export-processes', 'import-processes']);
        $otherImporter = $this->makeUser(['export-processes', 'import-processes']);

        $ownerMarker = 'script-source-marker-owner';
        $ownerPlaceholder = 'placeholder-environment-value-owner';
        $otherMarker = 'script-source-marker-other';
        $otherPlaceholder = 'placeholder-environment-value-other';

        $ownerPackage = $this->exportEncryptedPackage($owner, $ownerMarker, 'ImportLeakProbeOwner', $ownerPlaceholder);
        $otherPackage = $this->exportEncryptedPackage($otherImporter, $otherMarker, 'ImportLeakProbeOther', $otherPlaceholder);

        $this->queuePreview($owner, $ownerPackage)->assertOk();
        $this->queuePreview($otherImporter, $otherPackage)->assertOk();

        $ownerManifest = $this->actingAs($owner, 'api')
            ->getJson(route('api.import.get-import-manifest'));
        $otherManifest = $this->actingAs($otherImporter, 'api')
            ->getJson(route('api.import.get-import-manifest'));

        $this->assertManifestContainsPackage($ownerManifest->getContent(), $ownerMarker, $ownerPlaceholder);
        $this->assertManifestHidesPackage($ownerManifest, $otherMarker, $otherPlaceholder);
        $this->assertManifestContainsPackage($otherManifest->getContent(), $otherMarker, $otherPlaceholder);
        $this->assertManifestHidesPackage($otherManifest, $ownerMarker, $ownerPlaceholder);
    }

    public function testSecondImporterCannotDownloadSharedDebugPackage(): void
    {
        $owner = $this->makeUser(['export-processes', 'import-processes']);
        $otherImporter = $this->makeUser(['import-processes']);

        $marker = 'script-source-marker-owner';
        $envPlaceholder = 'placeholder-environment-value';
        $package = $this->exportEncryptedPackage($owner, $marker, 'ImportLeakProbe', $envPlaceholder);
        $preview = $this->queuePreview($owner, $package);
        $preview->assertOk();

        // Preview does not write options. The download adds every shared import
        // file, so put a placeholder options file the same way a queued import does.
        Storage::put(ImportV2::OPTIONS_PATH, '{}');
        Storage::delete(ImportV2::DEBUG_ZIP_PATH);

        $download = $this->actingAs($otherImporter, 'web')
            ->get(route('import.download-debug', ['hash' => $preview->json('hash')]));

        if ($download->isSuccessful()) {
            $entries = $this->responseBody($download);
            $this->assertFalse(str_contains($entries, $marker), 'Debug download returned another user\'s script.');
            $this->assertFalse(str_contains($entries, $envPlaceholder), 'Debug download returned another user\'s environment value.');
        }
        $this->assertFalse(
            Storage::exists(ImportV2::DEBUG_ZIP_PATH),
            'Debug download must not write the shared package before authorization succeeds.'
        );
    }

    public function testSecondImporterCannotImportOrRewriteSharedPackage(): void
    {
        $owner = $this->makeUser(['export-processes', 'import-processes']);
        $otherImporter = $this->makeUser(['import-processes']);

        $marker = 'script-source-marker-owner';
        $envName = 'ImportLeakProbe';
        $envPlaceholder = 'placeholder-environment-value';
        $package = $this->exportEncryptedPackage($owner, $marker, $envName, $envPlaceholder);

        Script::query()->where('code', 'like', '%' . $marker . '%')->delete();
        EnvironmentVariable::query()->where('name', $envName)->delete();

        $preview = $this->queuePreview($owner, $package);
        $preview->assertOk();

        $probe = 'second-importer-options';
        $import = $this->actingAs($otherImporter, 'api')->postJson(route('api.import.do_import'), [
            'queue' => 1,
            'hash' => $preview->json('hash'),
            'password' => self::PACKAGE_PASSWORD,
            'options' => [
                'probe' => $probe,
            ],
        ]);
        $this->assertSame(0, Script::query()->where('code', 'like', '%' . $marker . '%')->count());
        $this->assertSame(0, EnvironmentVariable::query()->where('name', $envName)->count());
        $storedOptions = Storage::exists(ImportV2::OPTIONS_PATH)
            ? (string) Storage::get(ImportV2::OPTIONS_PATH)
            : '';
        $this->assertFalse(
            str_contains($storedOptions, $probe),
            'Queued import wrote options onto the shared import path.'
        );

        $ownerManifest = $this->actingAs($owner, 'api')
            ->getJson(route('api.import.get-import-manifest'));
        $this->assertManifestContainsPackage($ownerManifest->getContent(), $marker, $envPlaceholder);
    }

    private function assertManifestContainsPackage(string $body, string $marker, string $envPlaceholder): void
    {
        $this->assertTrue(str_contains($body, $marker), 'Uploader cannot read their own script from the queued manifest.');
        $this->assertTrue(str_contains($body, $envPlaceholder), 'Uploader cannot read their own environment value from the queued manifest.');
        $this->assertTrue(
            str_contains($body, DependentType::ENVIRONMENT_VARIABLE_VALUE),
            'Uploader manifest is missing the environment-variable reference.'
        );
    }

    private function assertManifestHidesPackage($response, string $marker, string $envPlaceholder): void
    {
        if ($response->status() !== 200) {
            $this->assertContains($response->status(), [401, 403, 404]);

            return;
        }

        $body = $response->getContent();
        $this->assertFalse(str_contains($body, $marker), 'A different importer received the queued script.');
        $this->assertFalse(str_contains($body, $envPlaceholder), 'A different importer received the queued environment value.');
    }

    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create(['is_administrator' => false]);
        $user->giveDirectPermission($permissions);

        return $user;
    }

    private function exportEncryptedPackage(User $owner, string $marker, string $envName, string $envPlaceholder): string
    {
        $environmentVariable = EnvironmentVariable::factory()->create([
            'name' => $envName,
            'description' => 'Import probe',
            'value' => $envPlaceholder,
        ]);
        $script = Script::factory()->create([
            'title' => 'Import probe ' . $envName,
            'language' => 'php',
            'code' => "<?php /* {$marker} */ return env('{$envName}');",
            'run_as_user_id' => $owner->id,
        ]);

        $export = $this->actingAs($owner, 'api')->postJson(route('api.export.download', [
            'type' => 'script',
            'id' => $script->id,
        ]), [
            'password' => self::PACKAGE_PASSWORD,
            'options' => [],
        ]);
        $export->assertOk();

        $payload = $export->streamedContent();
        $decoded = json_decode($payload, true);
        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['encrypted'] ?? false);
        $this->assertIsString($decoded['export']);
        $this->assertStringNotContainsString($envPlaceholder, $payload);

        unset($environmentVariable);

        return $payload;
    }

    private function queuePreview(User $owner, string $package)
    {
        $file = UploadedFile::fake()->createWithContent('package.json', $package);

        return $this->actingAs($owner, 'api')->post(route('api.import.preview'), [
            'file' => $file,
            'queue' => '1',
            'password' => self::PACKAGE_PASSWORD,
        ]);
    }

    private function responseBody($response): string
    {
        $base = $response->baseResponse;
        $raw = $base instanceof BinaryFileResponse
            ? (string) file_get_contents($base->getFile()->getPathname())
            : (string) $response->getContent();

        if (!str_starts_with($raw, "PK\x03\x04")) {
            return $raw;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'import-debug');
        file_put_contents($temporary, $raw);
        $zip = new ZipArchive();
        if ($zip->open($temporary) !== true) {
            unlink($temporary);

            return $raw;
        }

        $contents = '';
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $contents .= (string) $zip->getFromIndex($index);
        }
        $zip->close();
        unlink($temporary);

        return $contents;
    }
}
