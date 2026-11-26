<?php

namespace Tests\Unit;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use ProcessMaker\Events\ImportLog;
use ProcessMaker\Exception\UnsupportedImportAssetException;
use ProcessMaker\ImportExport\Importer;
use ProcessMaker\ImportExport\Options;
use ProcessMaker\ImportExport\UnsupportedAssetValidator;
use ProcessMaker\Jobs\ImportV2;
use ProcessMaker\Package\PackageVocabularies\ImportExport\VocabularyExporter;
use ProcessMaker\Package\PackageVocabularies\Models\Vocabulary;

class ImportUnsupportedVocabularyTest extends TestCase
{
    public static function vocabularyReferences(): array
    {
        $model = Vocabulary::class;
        $exporter = VocabularyExporter::class;

        return [
            'model without an installed package' => [['model' => $model]],
            'exporter without an installed package' => [['exporter' => $exporter]],
            'dependent model without a standalone asset' => [['dependents' => [['modelClass' => $model]]]],
            'dependent exporter without a standalone asset' => [['dependents' => [['exporterClass' => $exporter]]]],
            'case insensitive class with leading separator' => [['model' => '\\' . strtoupper($model)]],
        ];
    }

    #[DataProvider('vocabularyReferences')]
    public function testRejectsVocabularyBeforeResolvingAnyModels(array $asset): void
    {
        Schema::shouldReceive('hasTable')->with('vocabularies')->andReturnFalse();

        // This first asset must never be resolved: validation rejects the whole package first.
        $payload = ['root' => 'process', 'export' => [
            'process' => ['model' => 'MissingProcessModel', 'exporter' => 'MissingProcessExporter'],
            'vocabulary' => $asset,
        ]];

        $this->expectException(UnsupportedImportAssetException::class);
        $this->expectExceptionMessage('Vocabulary resources, which are not supported for import');

        (new Importer($payload, new Options([])))->previewImport();
    }

    private function useTestClassesForVocabularyRule(array $requiredClasses): void
    {
        // Keep the production table requirements while making class availability independent of installed packages.
        $rule = config('import-export.unsupported_assets.0');
        $rule['namespace_prefixes'] = [__NAMESPACE__];
        $rule['available_when']['classes'] = $requiredClasses;
        config(['import-export.unsupported_assets' => [$rule]]);
    }

    public function testCompatibleDestinationAllowsVocabularyAssetsAndDependents(): void
    {
        $this->useTestClassesForVocabularyRule([self::class]);
        foreach (['vocabularies', 'vocabulary_versions', 'process_vocabulary'] as $table) {
            Schema::shouldReceive('hasTable')->once()->with($table)->andReturnTrue();
        }

        (new UnsupportedAssetValidator())->validate([[
            'model' => '\\' . strtoupper(self::class),
            'dependents' => [['exporterClass' => self::class]],
        ]]);
    }

    public static function missingVocabularyTables(): array
    {
        return [
            'models' => ['vocabularies'],
            'versions' => ['vocabulary_versions'],
            'process associations' => ['process_vocabulary'],
        ];
    }

    #[DataProvider('missingVocabularyTables')]
    public function testVocabularyIsRejectedWhenARequiredTableIsMissing(string $missingTable): void
    {
        $this->useTestClassesForVocabularyRule([self::class]);
        Schema::shouldReceive('hasTable')->andReturnUsing(fn (string $table) => $table !== $missingTable);

        $this->expectException(UnsupportedImportAssetException::class);
        $this->expectExceptionMessage('Vocabulary resources, which are not supported for import');

        (new UnsupportedAssetValidator())->validate([['model' => self::class]]);
    }

    public function testVocabularyIsRejectedBeforeCheckingTablesWhenARequiredClassIsMissing(): void
    {
        $this->useTestClassesForVocabularyRule([__NAMESPACE__ . '\\MissingVocabularyModel']);
        Schema::shouldReceive('hasTable')->never();

        $this->expectException(UnsupportedImportAssetException::class);

        (new UnsupportedAssetValidator())->validate([['model' => self::class]]);
    }

    public function testMissingReferencedClassIsRejectedEvenWhenVocabularyIsAvailable(): void
    {
        $this->useTestClassesForVocabularyRule([self::class]);
        Schema::shouldReceive('hasTable')->andReturnTrue();

        $this->expectException(UnsupportedImportAssetException::class);

        (new UnsupportedAssetValidator())->validate([[
            'dependents' => [['exporterClass' => __NAMESPACE__ . '\\MissingVocabularyExporter']],
        ]]);
    }

    public static function additionalReferences(): array
    {
        return [
            'exact class' => [['model' => '\\Example\\RetiredAsset']],
            'case insensitive exact class' => [['exporter' => 'EXAMPLE\\RETIREDASSET']],
            'namespace with trailing separator' => [['model' => 'Example\\Legacy\\Models\\Asset']],
            'dependent namespace' => [['dependents' => [['exporterClass' => 'example\\legacy\\AssetExporter']]]],
        ];
    }

    #[DataProvider('additionalReferences')]
    public function testNewRulesRejectResourcesWithoutInstallingClasses(array $asset): void
    {
        $rules = config('import-export.unsupported_assets');
        $rules[] = [
            'name' => 'Legacy Example',
            'classes' => ['Example\\RetiredAsset'],
            'namespace_prefixes' => ['\\Example\\Legacy\\'],
        ];
        config(['import-export.unsupported_assets' => $rules]);

        $this->expectException(UnsupportedImportAssetException::class);
        $this->expectExceptionMessage('Legacy Example resources, which are not supported for import');

        (new UnsupportedAssetValidator())->validate([$asset]);
    }

    public function testSimilarClassNamesAndAllowedResourcesAreNotRejected(): void
    {
        $rules = config('import-export.unsupported_assets');
        $rules[] = [
            'name' => 'Legacy Example',
            'classes' => ['Example\\RetiredAsset'],
            'namespace_prefixes' => ['Example\\Legacy'],
        ];
        config(['import-export.unsupported_assets' => $rules]);
        Schema::shouldReceive('hasTable')->never();
        $assets = [
            ['model' => 'ProcessMaker\\Package\\PackageVocabulariesReplacement\\Models\\Vocabulary'],
            ['model' => 'Example\\RetiredAssetReplacement'],
            ['exporter' => 'Example\\LegacyReplacement\\Exporter'],
            ['model' => 'Example\\Current\\Asset'],
            ['dependents' => [['modelClass' => 'Example\\RetiredAsset\\Child']]],
        ];

        (new UnsupportedAssetValidator())->validate($assets);
        $this->assertFalse(class_exists('Example\\RetiredAssetReplacement'));
    }

    public function testResourcesAreAllowedWhenNoRulesAreConfigured(): void
    {
        config(['import-export.unsupported_assets' => []]);

        (new UnsupportedAssetValidator())->validate([
            ['model' => Vocabulary::class],
        ]);
        $this->assertSame([], config('import-export.unsupported_assets'));
    }

    public function testValidationResponseContainsOnlyActionableMessages(): void
    {
        config(['app.debug' => true]);
        $message = 'Vocabulary resources cannot be imported.';
        $request = Request::create('/api/1.0/import/preview', 'POST');
        $request->headers->set('Accept', 'application/json');
        $response = app(ExceptionHandler::class)->render(
            $request,
            new UnsupportedImportAssetException($message)
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'error' => $message,
            'message' => $message,
        ], $response->getData(true));
    }

    public static function importModes(): array
    {
        return ['preview' => [true], 'import' => [false]];
    }

    #[DataProvider('importModes')]
    public function testQueuedValidationReportsACleanErrorAndReleasesTheLock(bool $preview): void
    {
        config(['cache.default' => 'array']);
        Schema::shouldReceive('hasTable')->with('vocabularies')->andReturnFalse();
        Storage::fake();
        Event::fake([ImportLog::class]);
        $payload = json_encode(['root' => 'vocabulary', 'export' => [
            'vocabulary' => ['model' => Vocabulary::class],
        ]]);
        Storage::put(ImportV2::FILE_PATH, $payload);
        Storage::put(ImportV2::OPTIONS_PATH, '{}');

        $checksum = Storage::checksum(ImportV2::FILE_PATH, ['checksum_algo' => 'md5']);
        (new ImportV2(1, null, $checksum, $preview))->handle();

        Event::assertDispatched(ImportLog::class, function ($event) {
            return $event->type === 'error'
                && str_starts_with($event->message, 'This process package contains Vocabulary resources,')
                && !str_contains($event->message, 'Exception')
                && !str_contains($event->message, '#0');
        });
        Event::assertNotDispatched(ImportLog::class, fn ($event) => $event->message === 'preview' || $event->message === 'Done');
        $this->assertFalse(Storage::exists(ImportV2::MANIFEST_PATH));
        $lock = Cache::lock(ImportV2::CACHE_LOCK_KEY, ImportV2::RELEASE_LOCK_AFTER);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function testUnexpectedFailureKeepsTheLockUntilTheFailureCallback(): void
    {
        config(['cache.default' => 'array']);
        Storage::fake();
        Event::fake([ImportLog::class]);
        Storage::put(ImportV2::FILE_PATH, '{}');
        $job = new ImportV2(1, null, 'incorrect-hash', true);

        try {
            $job->handle();
            $this->fail('An invalid hash must fail the import.');
        } catch (\Exception $exception) {
            $this->assertSame('File hash does not match. Is another user importing?', $exception->getMessage());
        }

        $nextLock = Cache::lock(ImportV2::CACHE_LOCK_KEY, ImportV2::RELEASE_LOCK_AFTER);
        $this->assertFalse($nextLock->get());
        $job->failed($exception);
        $this->assertTrue($nextLock->get());
        $nextLock->release();
    }
}
