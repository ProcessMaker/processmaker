<?php

namespace Tests\Feature;

use Tests\TestCase;
use ProcessMaker\Managers\ScreenCompiledManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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
    public function it_stores_compiled_content()
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
    public function it_retrieves_compiled_content()
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
    public function it_returns_null_when_compiled_content_does_not_exist()
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
    public function it_clears_all_compiled_assets()
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
    public function it_clears_process_screens_cache()
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
        $otherScreenKey = "pid_999_v1_en_sid_3_v1";

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
     * Validate a screen key can be created
     *
     * @test
     */
    public function it_creates_a_screen_key()
    {
        // Arrange
        $manager = new ScreenCompiledManager();
        $processId = '123';
        $processVersionId = '1';
        $language = 'en';
        $screenId = '456';
        $screenVersionId = '1';

        $expectedKey = "pid_123_1_en_sid_456_1";

        // Create the screen key
        $screenKey = $manager->createKey($processId, $processVersionId, $language, $screenId, $screenVersionId);

        // Assert
        $this->assertEquals($expectedKey, $screenKey);
    }

    /**
     * Validate the last screen version ID can be retrieved
     *
     * @test
     */
    public function it_gets_the_last_screen_version_id()
    {
        // Create the manager
        $manager = new ScreenCompiledManager();
        $expectedId = 999;

        // Mock the DB facade
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT id FROM screen_versions ORDER BY id DESC LIMIT 1;')
            ->andReturn([(object)['id' => $expectedId]]);

        // Get the last screen version ID
        $lastId = $manager->getLastScreenVersionId();

        // Assert the ID is the expected one
        $this->assertEquals($expectedId, $lastId);
    }
}
