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
            'hello' => 'Hello',
            'world' => 'World',
        ];
        Storage::disk('lang')->put('en.json', json_encode($existingTranslations, JSON_PRETTY_PRINT));

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

        // Verify merged content
        $mergedContent = Storage::disk('lang')->get('en.json');
        $mergedTranslations = json_decode($mergedContent, true);
        $expectedMerged = [
            'hello' => 'Hello',
            'world' => 'World',
            'welcome' => 'Welcome',
            'goodbye' => 'Goodbye',
        ];
        $this->assertEquals($expectedMerged, $mergedTranslations);
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
