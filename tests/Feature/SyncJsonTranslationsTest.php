<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Helpers\SyncJsonTranslations;
use Tests\TestCase;

class SyncJsonTranslationsTest extends TestCase
{
    protected $tempDir;

    protected $syncTranslations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for resources-core
        $this->tempDir = sys_get_temp_dir() . '/sync_translations_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/lang', 0755, true);

        // Set the resources-core path to our temp directory
        Config::set('app.resources_core_path', $this->tempDir);

        // Create fake storage for lang disk
        Storage::fake('lang');

        $this->syncTranslations = new SyncJsonTranslations();
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Test copying a new translation file when destination doesn't exist
     */
    public function testCopyNewTranslationFile()
    {
        // Create a test JSON file in resources-core
        $testTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
        ];

        $this->createTestFile('en.json', $testTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals('copied', $results['en']['action']);
        $this->assertEquals(3, $results['en']['total_keys']);
        $this->assertNull($results['en']['error']);

        // Verify file was copied correctly
        $this->assertTrue(Storage::disk('lang')->exists('en.json'));
        $copiedContent = Storage::disk('lang')->get('en.json');
        $copiedTranslations = json_decode($copiedContent, true);
        $this->assertEquals($testTranslations, $copiedTranslations);
    }

    /**
     * Test merging new translations into existing file
     */
    public function testMergeNewTranslations()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'hello' => 'Hello Customized',
            'world' => 'World',
        ];
        foreach (['en', 'es', 'fr', 'de'] as $language) {
            Storage::disk('lang')->put($language . '.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));
        }

        // Create resources-core with additional translations
        $resourcesCoreTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
            'goodbye' => 'Goodbye',
        ];
        $this->createTestFile('en.json', $resourcesCoreTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals('merged', $results['en']['action']);
        $this->assertEquals(2, $results['en']['new_keys']);
        $this->assertEquals(4, $results['en']['total_keys']);
        $this->assertNull($results['en']['error']);

        $otherLanguageResults = $results['en']['otherLanguageResults'];

        // Make sure the results show the other language files were updated
        $this->assertArrayHasKey('es', $otherLanguageResults);
        $this->assertEquals('updated', $otherLanguageResults['es']['action']);
        $this->assertEquals(2, $otherLanguageResults['es']['new_keys']);
        $this->assertEquals(4, $otherLanguageResults['es']['total_keys']);
        $this->assertNull($otherLanguageResults['es']['error']);

        $this->assertArrayHasKey('fr', $otherLanguageResults);
        $this->assertEquals('updated', $otherLanguageResults['fr']['action']);
        $this->assertEquals(2, $otherLanguageResults['fr']['new_keys']);
        $this->assertEquals(4, $otherLanguageResults['fr']['total_keys']);
        $this->assertNull($otherLanguageResults['fr']['error']);

        $this->assertArrayHasKey('de', $otherLanguageResults);
        $this->assertEquals('updated', $otherLanguageResults['de']['action']);
        $this->assertEquals(2, $otherLanguageResults['de']['new_keys']);
        $this->assertEquals(4, $otherLanguageResults['de']['total_keys']);
        $this->assertNull($otherLanguageResults['de']['error']);

        // Verify merged content
        $mergedContent = Storage::disk('lang')->get('en.json');
        $mergedTranslations = json_decode($mergedContent, true);
        $expectedMerged = [
            'hello' => 'Hello Customized',
            'world' => 'World',
            'welcome' => 'Welcome',
            'goodbye' => 'Goodbye',
        ];
        $this->assertEquals($expectedMerged, $mergedTranslations);

        // Verify that the other language files have the new keys
        // with empty values because the file being merged is en.json.

        $esTranslations = Storage::disk('lang')->get('es.json');
        $esTranslations = json_decode($esTranslations, true);
        $this->assertEquals('World', $esTranslations['world']);
        $this->assertEquals('', $esTranslations['welcome']);
        $this->assertEquals('', $esTranslations['goodbye']);

        $frTranslations = Storage::disk('lang')->get('fr.json');
        $frTranslations = json_decode($frTranslations, true);
        $this->assertEquals('World', $esTranslations['world']);
        $this->assertEquals('', $frTranslations['welcome']);
        $this->assertEquals('', $frTranslations['goodbye']);

        $deTranslations = Storage::disk('lang')->get('de.json');
        $deTranslations = json_decode($deTranslations, true);
        $this->assertEquals('World', $esTranslations['world']);
        $this->assertEquals('', $deTranslations['welcome']);
        $this->assertEquals('', $deTranslations['goodbye']);
    }

    /**
     * Test no changes when all translations already exist
     */
    public function testNoChangesWhenAllTranslationsExist()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
        ];
        Storage::disk('lang')->put('en.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));

        // Create resources-core with same translations
        $this->createTestFile('en.json', $existingTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals('no_changes', $results['en']['action']);
        $this->assertEquals(0, $results['en']['new_keys']);
        $this->assertEquals(3, $results['en']['total_keys']);
        $this->assertNull($results['en']['error']);
    }

    /**
     * Test handling invalid JSON in resources-core
     */
    public function testInvalidJsonInResourcesCore()
    {
        // Create invalid JSON file in resources-core
        file_put_contents($this->tempDir . '/lang/en.json', '{"hello": "Hello", "world":}');

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals('none', $results['en']['action']);
        $this->assertNotNull($results['en']['error']);
        $this->assertStringContainsString('Invalid JSON', $results['en']['error']);
    }

    /**
     * Test handling invalid JSON in destination
     */
    public function testInvalidJsonInDestination()
    {
        // Create invalid JSON in destination
        Storage::disk('lang')->put('en.json', '{"hello": "Hello", "world":}');

        // Create valid JSON in resources-core
        $validTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
        ];
        $this->createTestFile('en.json', $validTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertEquals('none', $results['en']['action']);
        $this->assertNotNull($results['en']['error']);
        $this->assertStringContainsString('Invalid JSON', $results['en']['error']);
    }

    /**
     * Test processing multiple language files
     */
    public function testProcessMultipleLanguages()
    {
        // Create multiple language files in resources-core
        $this->createTestFile('en.json', ['hello' => 'Hello', 'world' => 'World']);
        $this->createTestFile('es.json', ['hola' => 'Hola', 'mundo' => 'Mundo']);
        $this->createTestFile('fr.json', ['bonjour' => 'Bonjour', 'monde' => 'Monde']);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertArrayHasKey('en', $results);
        $this->assertArrayHasKey('es', $results);
        $this->assertArrayHasKey('fr', $results);

        $this->assertEquals('copied', $results['en']['action']);
        $this->assertEquals('copied', $results['es']['action']);
        $this->assertEquals('copied', $results['fr']['action']);

        // Verify all files were created
        $this->assertTrue(Storage::disk('lang')->exists('en.json'));
        $this->assertTrue(Storage::disk('lang')->exists('es.json'));
        $this->assertTrue(Storage::disk('lang')->exists('fr.json'));
    }

    /**
     * Test preserving existing custom translations
     */
    public function testPreserveExistingCustomTranslations()
    {
        // Create existing translations with custom values
        $existingTranslations = [
            'hello' => 'Custom Hello',
            'world' => 'Custom World',
            'custom_key' => 'Custom Value',
        ];
        Storage::disk('lang')->put('en.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));

        // Create resources-core with different values for existing keys and new keys
        $resourcesCoreTranslations = [
            'hello' => 'Default Hello',
            'world' => 'Default World',
            'welcome' => 'Welcome',
            'goodbye' => 'Goodbye',
        ];
        $this->createTestFile('en.json', $resourcesCoreTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('merged', $results['en']['action']);
        $this->assertEquals(2, $results['en']['new_keys']); // welcome and goodbye
        $this->assertEquals(5, $results['en']['total_keys']); // hello, world, custom_key, welcome, goodbye

        // Verify custom translations were preserved
        $mergedContent = Storage::disk('lang')->get('en.json');
        $mergedTranslations = json_decode($mergedContent, true);

        $this->assertEquals('Custom Hello', $mergedTranslations['hello']); // Preserved custom value
        $this->assertEquals('Custom World', $mergedTranslations['world']); // Preserved custom value
        $this->assertEquals('Custom Value', $mergedTranslations['custom_key']); // Preserved custom key
        $this->assertEquals('Welcome', $mergedTranslations['welcome']); // New from resources-core
        $this->assertEquals('Goodbye', $mergedTranslations['goodbye']); // New from resources-core
    }

    /**
     * Test backup creation when merging translations
     */
    public function testBackupCreationWhenMerging()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
        ];
        foreach (['en', 'es', 'fr', 'de'] as $language) {
            Storage::disk('lang')->put($language . '.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));
        }

        // Create resources-core with additional translations
        $resourcesCoreTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
        ];
        $this->createTestFile('en.json', $resourcesCoreTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('merged', $results['en']['action']);
        $this->assertTrue($results['en']['backup_created']);

        // Verify backup file was created
        $backupFiles = $this->getBackupFiles('en.json');
        $this->assertCount(1, $backupFiles);

        // Verify backup content matches original
        $backupContent = Storage::disk('lang')->get($backupFiles[0]);
        $backupTranslations = json_decode($backupContent, true);
        $this->assertEquals($existingTranslations, $backupTranslations);

        // Also, verify that the other language files have backups
        $esBackupFiles = $this->getBackupFiles('es.json');
        $this->assertCount(1, $esBackupFiles);

        $frBackupFiles = $this->getBackupFiles('fr.json');
        $this->assertCount(1, $frBackupFiles);

        $deBackupFiles = $this->getBackupFiles('de.json');
        $this->assertCount(1, $deBackupFiles);
    }

    /**
     * Test no backup creation when no changes are made
     */
    public function testNoBackupCreationWhenNoChanges()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
        ];
        Storage::disk('lang')->put('en.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));

        // Create resources-core with same translations
        $this->createTestFile('en.json', $existingTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('no_changes', $results['en']['action']);
        $this->assertFalse($results['en']['backup_created']);

        // Verify no backup files were created
        $backupFiles = $this->getBackupFiles('en.json');
        $this->assertCount(0, $backupFiles);
    }

    /**
     * Test backup rotation (keeping only 3 most recent backups)
     */
    public function testBackupRotation()
    {
        // Create existing translations in destination
        $existingTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
        ];
        Storage::disk('lang')->put('en.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));

        // Run sync multiple times to create multiple backups
        for ($i = 0; $i < 7; $i++) {
            // Create resources-core with additional translations (different each time)
            $resourcesCoreTranslations = [
                'hello' => 'Hello',
                'world' => 'World',
                'welcome' => 'Welcome',
            ];

            // Add a new key each time to ensure changes are detected
            $resourcesCoreTranslations['new_key_' . $i] = 'Value ' . $i;

            $this->createTestFile('en.json', $resourcesCoreTranslations);

            $results = $this->syncTranslations->sync();

            // Small delay to ensure different timestamps
            usleep(1000);
        }

        // Verify only 3 backup files exist
        $backupFiles = $this->getBackupFiles('en.json');
        $this->assertCount(5, $backupFiles);
    }

    /**
     * Test backup creation when copying new files
     */
    public function testNoBackupCreationWhenCopying()
    {
        // Create a test JSON file in resources-core
        $testTranslations = [
            'hello' => 'Hello',
            'world' => 'World',
        ];
        $this->createTestFile('en.json', $testTranslations);

        // Run sync
        $results = $this->syncTranslations->sync();

        // Assert results
        $this->assertEquals('copied', $results['en']['action']);
        $this->assertFalse($results['en']['backup_created']);

        // Verify no backup files were created
        $backupFiles = $this->getBackupFiles('en.json');
        $this->assertCount(0, $backupFiles);
    }

    /**
     * Helper method to get backup files for a given file
     */
    private function getBackupFiles(string $filename): array
    {
        $backupFiles = [];
        $files = Storage::disk('lang')->files();

        foreach ($files as $file) {
            if (preg_match('/^' . preg_quote($filename, '/') . '\.bak\.\d+(?:\.\d+)?$/', $file)) {
                $backupFiles[] = $file;
            }
        }

        return $backupFiles;
    }

    /**
     * Helper method to create test files
     */
    private function createTestFile(string $filename, array $translations): void
    {
        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->tempDir . '/lang/' . $filename, $content);
    }

    /**
     * Helper method to remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
