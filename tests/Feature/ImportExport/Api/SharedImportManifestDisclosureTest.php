<?php

namespace Tests\Feature\ImportExport\Api;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\ImportExport\ExportEncrypted;
use ProcessMaker\ImportExport\Exporters\ProcessExporter;
use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Models\EnvironmentVariable;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\Feature\ImportExport\HelperTrait;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Queued process import stores one payload and one manifest for the whole
 * instance. These tests require each preview, manifest read, debug download,
 * and queued import to stay bound to the user who uploaded that package.
 *
 * Screen-template validation is not requested separately: it is the same
 * TemplateController::preImportValidation method as the process-template case,
 * with a different package type string, and it calls ImportController::preview.
 * POST /processes/import?queue=1 is not covered: it stores the upload under a
 * unique imports/ path and dispatches ImportProcess, not these shared files.
 */
class SharedImportManifestDisclosureTest extends TestCase
{
    use HelperTrait;
    use RequestHelper {
        RequestHelper::setUp as requestHelperSetUp;
    }

    public $withPermissions = true;

    protected function setUp(): void
    {
        $this->requestHelperSetUp();
        $this->ensureLanguageLookup();
    }

    private const SCRIPT_MARKER = 'PLACEHOLDER_SCRIPT_SOURCE_SHARED_IMPORT';

    private const ENV_MARKER = 'PLACEHOLDER_ENV_VALUE_SHARED_IMPORT';

    private const ENV_NAME = 'PlaceholderEnvName';

    private const IMPORT_PASSWORD = 'placeholder-import-password';

    public function testQueuedPreviewManifestIsNotReadableByAnotherImporter(): void
    {
        $owner = $this->userWithPermissions(['export-processes', 'import-processes']);
        $otherImporter = $this->userWithPermissions(['import-processes']);
        $package = $this->makeEncryptedProcessPackage(
            'Shared import owner process',
            self::SCRIPT_MARKER,
            self::ENV_NAME,
            self::ENV_MARKER
        );

        $preview = $this->queuePreview($owner, route('api.import.preview'), $package['encrypted']);
        $preview->assertOk();
        $preview->assertJson(['queued' => true]);
        $hash = $preview->json('hash');
        $this->assertNotEmpty($hash);

        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);

        $this->assertImporterCannotReadManifest($otherImporter, $owner, $hash, self::SCRIPT_MARKER, self::ENV_MARKER);
        $this->assertImporterCannotReadManifest($otherImporter, $owner, $hash, self::SCRIPT_MARKER, self::ENV_MARKER, spoofOwner: true);

        $storedPayload = md5_file(Storage::path(ImportV2::FILE_PATH));
        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);
        $this->assertSame($storedPayload, md5_file(Storage::path(ImportV2::FILE_PATH)));
    }

    public function testProcessValidationQueueIsNotReadableByAnotherImporter(): void
    {
        $this->assertQueuedEntryPointIsPrivate(
            route('api.processes.preimportValidation'),
            'Shared import validation process'
        );
    }

    public function testTemplateValidationQueueIsNotReadableByAnotherImporter(): void
    {
        $owner = $this->userWithPermissions(['export-processes', 'import-processes']);
        $otherImporter = $this->userWithPermissions(['import-processes']);
        $package = $this->makeEncryptedProcessPackage(
            'Shared import template process',
            self::SCRIPT_MARKER,
            self::ENV_NAME,
            self::ENV_MARKER
        );
        $payload = $package['encrypted'];
        $payload['type'] = 'process_templates_package';

        $this->assertPreviewIsPrivate(
            $owner,
            $otherImporter,
            route('api.template.preImportValidation', ['type' => 'process']),
            $payload
        );
    }

    public function testConcurrentQueuedPreviewsStayWithTheUploader(): void
    {
        $first = $this->userWithPermissions(['export-processes', 'import-processes']);
        $second = $this->userWithPermissions(['export-processes', 'import-processes']);

        $firstPackage = $this->makeEncryptedProcessPackage(
            'Shared import first process',
            'PLACEHOLDER_SCRIPT_SOURCE_FIRST',
            'PlaceholderEnvNameFirst',
            'PLACEHOLDER_ENV_VALUE_FIRST'
        );
        $secondPackage = $this->makeEncryptedProcessPackage(
            'Shared import second process',
            'PLACEHOLDER_SCRIPT_SOURCE_SECOND',
            'PlaceholderEnvNameSecond',
            'PLACEHOLDER_ENV_VALUE_SECOND'
        );

        $firstPreview = $this->queuePreview($first, route('api.import.preview'), $firstPackage['encrypted']);
        $firstPreview->assertOk();
        $secondPreview = $this->queuePreview($second, route('api.import.preview'), $secondPackage['encrypted']);
        $secondPreview->assertOk();

        $this->assertOwnerManifestContainsPackage($first, 'PLACEHOLDER_SCRIPT_SOURCE_FIRST', 'PLACEHOLDER_ENV_VALUE_FIRST');
        $this->assertOwnerManifestExcludes($first, 'PLACEHOLDER_SCRIPT_SOURCE_SECOND', 'PLACEHOLDER_ENV_VALUE_SECOND');
        $this->assertOwnerManifestContainsPackage($second, 'PLACEHOLDER_SCRIPT_SOURCE_SECOND', 'PLACEHOLDER_ENV_VALUE_SECOND');
        $this->assertOwnerManifestExcludes($second, 'PLACEHOLDER_SCRIPT_SOURCE_FIRST', 'PLACEHOLDER_ENV_VALUE_FIRST');
    }

    public function testQueuedImportDoesNotApplyAnotherUsersPackage(): void
    {
        $owner = $this->userWithPermissions(['export-processes', 'import-processes']);
        $otherImporter = $this->userWithPermissions(['import-processes']);
        $package = $this->makeEncryptedProcessPackage(
            'Shared import apply process',
            self::SCRIPT_MARKER,
            self::ENV_NAME,
            self::ENV_MARKER
        );

        $preview = $this->queuePreview($owner, route('api.import.preview'), $package['encrypted']);
        $preview->assertOk();
        $hash = $preview->json('hash');
        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);

        $package['process']->forceDelete();
        $package['script']->delete();
        $package['variable']->delete();
        $this->assertFalse(Process::where('name', 'Shared import apply process')->exists());

        $payloadMd5 = md5_file(Storage::path(ImportV2::FILE_PATH));
        $optionsBefore = Storage::get(ImportV2::OPTIONS_PATH);
        Storage::delete(ImportV2::DEBUG_ZIP_PATH);

        $this->user = $otherImporter;
        $import = $this->apiCall('POST', route('api.import.do_import'), [
            'queue' => 1,
            'hash' => $hash,
            'user_id' => $owner->id,
            'password' => self::IMPORT_PASSWORD,
            'options' => [],
        ]);
        $this->assertContains($import->status(), [403, 404]);
        $this->assertStringNotContainsString(self::SCRIPT_MARKER, $import->getContent());
        $this->assertStringNotContainsString(self::ENV_MARKER, $import->getContent());
        $this->assertFalse(Process::where('name', 'Shared import apply process')->exists());
        $this->assertFalse(Script::where('code', 'like', '%' . self::SCRIPT_MARKER . '%')->exists());
        $this->assertFalse(EnvironmentVariable::where('name', self::ENV_NAME)->exists());
        $this->assertSame($optionsBefore, Storage::get(ImportV2::OPTIONS_PATH));
        $this->assertSame($payloadMd5, md5_file(Storage::path(ImportV2::FILE_PATH)));

        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);
    }

    public function testDebugDownloadDoesNotReturnAnotherUsersPackage(): void
    {
        $owner = $this->userWithPermissions(['export-processes', 'import-processes']);
        $otherImporter = $this->userWithPermissions(['import-processes']);
        $package = $this->makeEncryptedProcessPackage(
            'Shared import debug process',
            self::SCRIPT_MARKER,
            self::ENV_NAME,
            self::ENV_MARKER
        );

        $preview = $this->queuePreview($owner, route('api.import.preview'), $package['encrypted']);
        $preview->assertOk();
        $hash = $preview->json('hash');

        // Preview does not write options.json. downloadImportDebug adds that path
        // to the zip, and a missing file aborts the response. The queued import
        // controller is what creates this file; its contents are not the secret.
        if (!Storage::exists(ImportV2::OPTIONS_PATH)) {
            Storage::put(ImportV2::OPTIONS_PATH, '[]');
        }
        if (!Storage::exists(ImportV2::LOG_PATH)) {
            Storage::put(ImportV2::LOG_PATH, '');
        }

        $payloadMd5 = md5_file(Storage::path(ImportV2::FILE_PATH));
        Storage::delete(ImportV2::DEBUG_ZIP_PATH);

        $this->user = $otherImporter;
        $debug = $this->webGet(route('import.download-debug'), [
            'hash' => $hash,
            'user_id' => $owner->id,
        ]);
        $this->_debug_response = null;
        $this->assertContains($debug->getStatusCode(), [403, 404]);
        $this->assertFalse(Storage::exists(ImportV2::DEBUG_ZIP_PATH));
        $this->assertSame($payloadMd5, md5_file(Storage::path(ImportV2::FILE_PATH)));
        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'is_administrator' => false,
        ]);
        $user->giveDirectPermission($permissions);
        $user->invalidatePermissionCache();

        return $user;
    }

    /**
     * @return array{encrypted: array, process: Process, script: Script, variable: EnvironmentVariable}
     */
    private function makeEncryptedProcessPackage(string $processName, string $scriptMarker, string $envName, string $envValue): array
    {
        $this->addGlobalSignalProcess();

        $bpmn = file_get_contents(base_path('tests/Feature/ImportExport/fixtures/process-with-task-script.bpmn.xml'));
        $process = Process::factory()->create([
            'name' => $processName,
            'status' => 'ACTIVE',
            'bpmn' => $bpmn,
        ]);
        $variable = EnvironmentVariable::factory()->create([
            'name' => $envName,
            'description' => 'placeholder description',
            'value' => $envValue,
        ]);
        $script = Script::factory()->create([
            'title' => $processName . ' script',
            'code' => '<?php $value = getenv(\'' . $envName . '\'); return \'' . $scriptMarker . '\';',
            'run_as_user_id' => User::factory()->create()->id,
        ]);
        Utils::setAttributeAtXPath($process, '/bpmn:definitions/bpmn:process/bpmn:scriptTask', 'pm:scriptRef', $script->id);
        $process->save();

        $plaintext = $this->export($process, ProcessExporter::class);
        $encodedPlaintext = json_encode($plaintext);
        $this->assertIsString($encodedPlaintext);
        $this->assertStringContainsString($scriptMarker, $encodedPlaintext);
        $this->assertStringContainsString($envValue, $encodedPlaintext);
        $this->assertStringContainsString('environment_variables_value', $encodedPlaintext);

        $encrypted = (new ExportEncrypted(self::IMPORT_PASSWORD))->call($plaintext);
        $encodedEncrypted = json_encode($encrypted);
        $this->assertIsString($encodedEncrypted);
        $this->assertStringNotContainsString($scriptMarker, $encodedEncrypted);
        $this->assertStringNotContainsString($envValue, $encodedEncrypted);

        return [
            'encrypted' => $encrypted,
            'process' => $process,
            'script' => $script,
            'variable' => $variable,
        ];
    }

    /**
     * ExporterBase::getExtraAttributes() always queries the translations Language model.
     * That package is not part of this repository. Load a lookup model with the columns
     * this repository already writes (code, name, installed) so preview can finish.
     */
    private function ensureLanguageLookup(): void
    {
        $class = \ProcessMaker\Package\Translations\Models\Language::class;
        if (!class_exists($class)) {
            require_once __DIR__ . '/Support/LanguageModel.php';
        }

        if (Schema::connection('processmaker')->hasTable('languages')) {
            return;
        }

        Schema::connection('processmaker')->create('languages', function ($table) {
            $table->increments('id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('installed')->default(0);
        });
    }

    private function queuePreview(User $user, string $url, array $payload)
    {
        $this->clearSharedImportFiles();
        config(['queue.default' => 'sync']);
        $this->user = $user;
        $encoded = json_encode($payload);

        return $this->apiCall('POST', $url, [
            'file' => UploadedFile::fake()->createWithContent('package.json', $encoded),
            'queue' => 1,
            'password' => self::IMPORT_PASSWORD,
        ]);
    }

    private function assertQueuedEntryPointIsPrivate(string $url, string $processName): void
    {
        $owner = $this->userWithPermissions(['export-processes', 'import-processes']);
        $otherImporter = $this->userWithPermissions(['import-processes']);
        $package = $this->makeEncryptedProcessPackage(
            $processName,
            self::SCRIPT_MARKER,
            self::ENV_NAME,
            self::ENV_MARKER
        );

        $this->assertPreviewIsPrivate($owner, $otherImporter, $url, $package['encrypted']);
    }

    private function assertPreviewIsPrivate(User $owner, User $otherImporter, string $url, array $payload): void
    {
        $preview = $this->queuePreview($owner, $url, $payload);
        $preview->assertOk();
        $preview->assertJson(['queued' => true]);
        $hash = $preview->json('hash');
        $this->assertNotEmpty($hash);

        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);
        $this->assertImporterCannotReadManifest($otherImporter, $owner, $hash, self::SCRIPT_MARKER, self::ENV_MARKER);
        $this->assertImporterCannotReadManifest($otherImporter, $owner, $hash, self::SCRIPT_MARKER, self::ENV_MARKER, spoofOwner: true);
        $this->assertOwnerManifestContainsPackage($owner, self::SCRIPT_MARKER, self::ENV_MARKER);
    }

    private function manifestSecrets(string $body): string
    {
        $manifest = json_decode($body, true);
        if (!is_array($manifest)) {
            return '';
        }

        $secrets = [];
        foreach ($manifest as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            $secrets[] = (string) ($asset['attributes']['code'] ?? '');
            $secrets[] = (string) ($asset['references']['environment_variables_value'] ?? '');
        }

        return implode("\n", $secrets);
    }

    private function assertOwnerManifestContainsPackage(User $owner, string $scriptMarker, string $envMarker): void
    {
        $this->user = $owner;
        $manifest = $this->apiCall('GET', route('api.import.get-import-manifest'));
        $manifest->assertOk();
        $secrets = $this->manifestSecrets($manifest->getContent());
        $this->assertStringContainsString($scriptMarker, $secrets);
        $this->assertStringContainsString($envMarker, $secrets);
    }

    private function assertOwnerManifestExcludes(User $owner, string $scriptMarker, string $envMarker): void
    {
        $this->user = $owner;
        $manifest = $this->apiCall('GET', route('api.import.get-import-manifest'));
        $manifest->assertOk();
        $secrets = $this->manifestSecrets($manifest->getContent());
        $this->assertStringNotContainsString($scriptMarker, $secrets);
        $this->assertStringNotContainsString($envMarker, $secrets);
    }

    private function assertImporterCannotReadManifest(
        User $otherImporter,
        User $owner,
        string $hash,
        string $scriptMarker,
        string $envMarker,
        bool $spoofOwner = false
    ): void {
        $this->user = $otherImporter;
        $params = [];
        if ($spoofOwner) {
            $params = [
                'hash' => $hash,
                'user_id' => $owner->id,
            ];
        }
        $manifest = $this->apiCall('GET', route('api.import.get-import-manifest'), $params);
        $this->assertContains($manifest->status(), [403, 404]);
        $this->assertStringNotContainsString($scriptMarker, $manifest->getContent());
        $this->assertStringNotContainsString($envMarker, $manifest->getContent());
    }

    private function clearSharedImportFiles(): void
    {
        Storage::delete([
            ImportV2::FILE_PATH,
            ImportV2::OPTIONS_PATH,
            ImportV2::MANIFEST_PATH,
            ImportV2::DEBUG_ZIP_PATH,
            ImportV2::LOG_PATH,
        ]);
        Cache::lock(ImportV2::CACHE_LOCK_KEY)->forceRelease();
    }
}
