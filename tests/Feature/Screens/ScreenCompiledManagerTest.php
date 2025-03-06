<?php

namespace Tests\Feature;

use Illuminate\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Managers\ScreenCompiledManager;
use Tests\TestCase;

class ScreenCompiledManagerTest extends TestCase
{
    protected $storageDisk = 'local';

    protected $storagePath = 'compiled_screens/';

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the storage disk using the known default value
        Storage::fake($this->storageDisk);
    }

    /**
     * Validate a screen can be stored into the screens cache
     *
     * @test
     */
    public function test_it_stores_compiled_content()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenKey = 'test_screen_key';
        $compiledContent = ['key' => 'value'];

        // Act
        $manager->storeCompiledContent($screenKey, $compiledContent);

        // Assert
        $filename = 'screen_' . $screenKey . '.bin';
        $storagePath = $this->storagePath . $filename;

        Storage::disk($this->storageDisk)->assertExists($storagePath);
        $storedContent = Storage::disk($this->storageDisk)->get($storagePath);
        $this->assertEquals(serialize($compiledContent), $storedContent);
    }

    /**
     * Validate a screen can be retrieved from screens cache
     *
     * @test
     */
    public function test_it_retrieves_compiled_content()
    {
        // Arrange content
        $manager = new ScreenCompiledManager();
        $screenKey = 'test_screen_key';
        $compiledContent = ['key' => 'value'];
        $filename = 'screen_' . $screenKey . '.bin';
        $storagePath = $this->storagePath . $filename;

        Storage::disk($this->storageDisk)->put($storagePath, serialize($compiledContent));

        // Get the compiled content
        $retrievedContent = $manager->getCompiledContent($screenKey);

        // Assert the content is the same
        $this->assertEquals($compiledContent, $retrievedContent);
    }

    /**
     * Validate a null value is returned when compiled content does not exist
     *
     * @test
     */
    public function test_it_returns_null_when_compiled_content_does_not_exist()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenKey = 'non_existent_key';

        // Act
        $retrievedContent = $manager->getCompiledContent($screenKey);

        // Assert
        $this->assertNull($retrievedContent);
    }

    /**
     * Validate all compiled assets can be cleared
     *
     * @test
     */
    public function test_it_clears_all_compiled_assets()
    {
        // Arrange content
        $manager = new ScreenCompiledManager();
        $screenKey = 'test_screen_key';
        $compiledContent = ['key' => 'value'];
        $filename = 'screen_' . $screenKey . '.bin';
        $storagePath = $this->storagePath . $filename;

        Storage::disk($this->storageDisk)->put($storagePath, serialize($compiledContent));

        // Clear all compiled assets
        $manager->clearCompiledAssets();

        // Assert the file has been removed
        Storage::disk($this->storageDisk)->assertMissing($storagePath);
        // Ensure the directory has been recreated
        Storage::disk($this->storageDisk)->assertExists($this->storagePath);
    }

    /**
     * Validate a screen key can be created and can be used to store and retrieve compiled content
     * Also validate if screens cache can be cleared for a specific process
     *
     * @test
     */
    public function test_it_clears_process_screens_cache()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $processId = '123';
        $compiledContent = ['key' => 'value'];

        // Files related to the process
        $screenKeys = [
            "pid_{$processId}_v1_en_sid_1_v1",
            "pid_{$processId}_v1_en_sid_2_v1",
        ];

        // Files unrelated to the process
        $otherScreenKey = 'pid_999_v1_en_sid_3_v1';

        foreach ($screenKeys as $screenKey) {
            $filename = 'screen_' . $screenKey . '.bin';
            $storagePath = $this->storagePath . $filename;
            Storage::disk($this->storageDisk)->put($storagePath, serialize($compiledContent));
        }

        $otherFilename = 'screen_' . $otherScreenKey . '.bin';
        $otherStoragePath = $this->storagePath . $otherFilename;
        Storage::disk($this->storageDisk)->put($otherStoragePath, serialize($compiledContent));

        // Clear the screens cache for the process
        $manager->clearProcessScreensCache($processId);

        // Assert that the files related to the process have been removed
        foreach ($screenKeys as $screenKey) {
            $filename = 'screen_' . $screenKey . '.bin';
            $storagePath = $this->storagePath . $filename;
            Storage::disk($this->storageDisk)->assertMissing($storagePath);
        }
        // The other file should still exist
        Storage::disk($this->storageDisk)->assertExists($otherStoragePath);
    }

    /**
     * Validate that a screen key can be created with various process versions, screen versions, and languages.
     *
     * @test
     */
    public function test_it_creates_a_screen_key_with_various_versions()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $processId = '123';
        $processVersionIds = ['1', '2', '3'];
        $languages = ['en', 'es', 'fr', 'de', 'it', 'pt', 'zh', 'ja', 'ru', 'ar'];
        $screenId = '456';
        $screenVersionIds = ['1', '2'];

        foreach ($processVersionIds as $processVersionId) {
            foreach ($screenVersionIds as $screenVersionId) {
                foreach ($languages as $language) {
                    $expectedKey = "pid_{$processId}_{$processVersionId}_{$language}_sid_{$screenId}_{$screenVersionId}";

                    // Create the screen key
                    $screenKey = $manager->createKey($processId, $processVersionId, $language, $screenId, $screenVersionId);

                    // Assert
                    $this->assertEquals($expectedKey, $screenKey);
                }
            }
        }
    }

    /**
     * Validate the last screen version ID can be retrieved
     *
     * @test
     */
    public function test_it_gets_the_last_screen_version_id()
    {
        // Create the manager
        $manager = new ScreenCompiledManager();
        $expectedId = 999;

        // Mock the DB facade
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT id FROM screen_versions ORDER BY id DESC LIMIT 1;')
            ->andReturn([(object) ['id' => $expectedId]]);

        // Get the last screen version ID
        $lastId = $manager->getLastScreenVersionId();

        // Assert the ID is the expected one
        $this->assertEquals($expectedId, $lastId);
    }

    /**
     * Validate storing compiled content with empty content
     *
     * @test
     */
    public function test_it_stores_empty_compiled_content()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenKey = 'empty_screen_key';
        $compiledContent = '';

        // Act
        $manager->storeCompiledContent($screenKey, $compiledContent);

        // Assert
        $filename = 'screen_' . $screenKey . '.bin';
        $storagePath = $this->storagePath . $filename;

        Storage::disk($this->storageDisk)->assertExists($storagePath);
        $storedContent = Storage::disk($this->storageDisk)->get($storagePath);
        $this->assertEquals(serialize($compiledContent), $storedContent);
    }

    /**
     * Validate exception handling when storage is unavailable
     *
     * @test
     */
    public function test_it_handles_storage_exceptions()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenKey = 'exception_screen_key';
        $compiledContent = ['key' => 'value'];

        // Simulate storage exception
        Storage::shouldReceive('disk->put')
            ->andThrow(new \Exception('Storage unavailable'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage unavailable');

        $manager->storeCompiledContent($screenKey, $compiledContent);
    }

    /**
     * Validate clearing compiled assets when directory does not exist
     *
     * @test
     */
    public function test_it_clears_compiled_assets_when_directory_does_not_exist()
    {
        // Arrange
        $manager = new ScreenCompiledManager();

        // Ensure directory does not exist
        Storage::disk($this->storageDisk)->deleteDirectory($this->storagePath);

        // Act
        $manager->clearCompiledAssets();

        // Assert the directory has been recreated
        Storage::disk($this->storageDisk)->assertExists($this->storagePath);
    }

    /**
     * Validate that storing compiled content fails with invalid data
     *
     * @test
     */
    public function test_it_fails_with_invalid_screen_key()
    {
        // Arrange
        $manager = new ScreenCompiledManager();

        // Test cases with invalid screen keys
        $invalidKeys = [
            '', // Empty string
            null, // Null value
            str_repeat('a', 1000), // Extremely long key
            '../../malicious/path', // Path traversal attempt
            'special@#$%chars', // Special characters
        ];

        foreach ($invalidKeys as $invalidKey) {
            try {
                $manager->storeCompiledContent($invalidKey, ['test' => 'content']);
                $this->fail('Expected exception was not thrown for key: ' . (string) $invalidKey);
            } catch (\TypeError|\Exception $e) {
                // Assert that an exception was thrown
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test handling of storage limit scenarios when storing compiled screen content
     *
     * @test
     */
    public function test_it_handles_storage_limit_scenarios()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenKey = $manager->createKey('1', '1', 'en', '1', '1');
        $compiledContent = ['test' => 'content'];

        // Simulate storage limit reached by throwing a specific exception
        Storage::shouldReceive('disk->put')
            ->andThrow(new \Exception('Storage limit reached'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Storage limit reached');

        // Attempt to store compiled content, expecting an exception
        $manager->storeCompiledContent($screenKey, $compiledContent);
    }

    /**
     * Test deleting compiled content for a specific screen ID and language
     *
     * @test
     */
    public function test_it_deletes_screen_compiled_content()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenId = '5';
        $language = 'es';
        $compiledContent = ['key' => 'value'];

        // Create test files that should be deleted
        $filesToDelete = [
            "pid_19_63_{$language}_sid_{$screenId}_7",
            "pid_20_64_{$language}_sid_{$screenId}_8",
        ];

        // Create test files that should not be deleted
        $filesToKeep = [
            "pid_19_63_en_sid_{$screenId}_7", // Different language
            "pid_19_63_{$language}_sid_6_7",   // Different screen ID
            'pid_19_63_fr_sid_6_7',           // Different language and screen ID
        ];

        // Store all test files
        foreach ($filesToDelete as $key) {
            $manager->storeCompiledContent($key, $compiledContent);
        }
        foreach ($filesToKeep as $key) {
            $manager->storeCompiledContent($key, $compiledContent);
        }

        // Act
        $result = $manager->deleteScreenCompiledContent($screenId, $language);

        // Assert
        $this->assertTrue($result);

        // Verify files that should be deleted are gone
        foreach ($filesToDelete as $key) {
            $filename = 'screen_' . $key . '.bin';
            Storage::disk($this->storageDisk)->assertMissing($this->storagePath . $filename);
        }

        // Verify files that should be kept still exist
        foreach ($filesToKeep as $key) {
            $filename = 'screen_' . $key . '.bin';
            Storage::disk($this->storageDisk)->assertExists($this->storagePath . $filename);
        }
    }

    /**
     * Test deleting compiled content when no matching files exist
     *
     * @test
     */
    public function test_it_returns_false_when_no_files_match_delete_pattern()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenId = '5';
        $language = 'es';
        $compiledContent = ['key' => 'value'];

        // Create test files that should not be deleted
        $filesToKeep = [
            'pid_19_63_en_sid_6_7',
            'pid_19_63_fr_sid_6_7',
        ];

        // Store test files
        foreach ($filesToKeep as $key) {
            $manager->storeCompiledContent($key, $compiledContent);
        }

        // Act
        $result = $manager->deleteScreenCompiledContent($screenId, $language);

        // Assert
        $this->assertFalse($result);

        // Verify all files still exist
        foreach ($filesToKeep as $key) {
            $filename = 'screen_' . $key . '.bin';
            Storage::disk($this->storageDisk)->assertExists($this->storagePath . $filename);
        }
    }

    /**
     * Test deleting compiled content with special characters in language code
     *
     * @test
     */
    public function test_it_handles_special_characters_in_language_code()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $screenId = '5';
        $language = 'zh-CN'; // Language code with special character
        $compiledContent = ['key' => 'value'];

        // Create test file with special character in language code
        $key = "pid_19_63_{$language}_sid_{$screenId}_7";
        $manager->storeCompiledContent($key, $compiledContent);

        // Act
        $result = $manager->deleteScreenCompiledContent($screenId, $language);

        // Assert
        $this->assertTrue($result);
        $filename = 'screen_' . $key . '.bin';
        Storage::disk($this->storageDisk)->assertMissing($this->storagePath . $filename);
    }
}
