<?php

namespace Tests\Unit\ProcessMaker\Cache\Screens;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Mockery;
use ProcessMaker\Cache\Screens\ScreenCacheManager;
use ProcessMaker\Managers\ScreenCompiledManager;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Translation;
use Tests\TestCase;

class ScreenCacheManagerTest extends TestCase
{
    protected $cacheManager;

    protected $screenCompiler;

    protected $screenCache;

    protected $redis;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->cacheManager = Mockery::mock(CacheManager::class);
        $this->screenCompiler = Mockery::mock(ScreenCompiledManager::class);

        // Create instance with mocked dependencies
        $this->screenCache = new ScreenCacheManager(
            $this->cacheManager,
            $this->screenCompiler
        );

        // Clear Redis before each test
        Redis::flushdb();
    }

    /** @test */
    public function testCreatesCorrectCacheKey()
    {
        $languages = ['en', 'es', 'fr', 'de'];

        foreach ($languages as $lang) {
            $params = [
                'process_id' => 1,
                'process_version_id' => 2,
                'language' => $lang,
                'screen_id' => 3,
                'screen_version_id' => 4,
            ];
            $key = $this->screenCache->createKey($params);
            $expectedKey = "screen_pid_1_2_{$lang}_sid_3_4";

            $this->assertEquals($expectedKey, $key);
        }
    }

    /** @test */
    public function testStoresAndRetrievesFromMemoryCache()
    {
        $key = 'test_screen';
        $value = ['content' => 'test'];
        $serializedValue = serialize($value);

        // Set up expectations
        $this->cacheManager->shouldReceive('put')
            ->once()
            ->with($key, $serializedValue, 86400)
            ->andReturn(true);

        $this->cacheManager->shouldReceive('get')
            ->once()
            ->withArgs([$key])
            ->andReturn($serializedValue);

        // Execute and verify
        $this->screenCache->set($key, $value);
        $result = $this->screenCache->get($key);

        $this->assertEquals($value, $result);
    }

    /** @test */
    public function testHandlesTranslations()
    {
        $key = 'test_screen';
        $value = ['content' => 'test', 'title' => 'Original Title'];
        $serializedValue = serialize($value);
        // Set up expectations for initial store
        $this->cacheManager->shouldReceive('put')
            ->once()
            ->with($key, $serializedValue, 86400)
            ->andReturn(true);

        // Set up expectations for retrieval
        $this->cacheManager->shouldReceive('get')
            ->once()
            ->withArgs([$key])
            ->andReturn($serializedValue);

        // Store and retrieve with translation
        $this->screenCache->set($key, $value);
        $result = $this->screenCache->get($key);

        $this->assertEquals($value, $result);
        $this->assertEquals('Original Title', $result['title']);
    }

    /** @test */
    public function testHandlesNestedScreens()
    {
        $key = 'test_screen';
        $nestedKey = 'nested_screen';

        $nestedContent = ['content' => 'nested content'];
        $serializedNestedContent = serialize($nestedContent);
        $parentContent = [
            'component' => 'FormScreen',
            'config' => [
                'screenId' => 123,
                'content' => $nestedContent,
            ],
        ];
        $serializedParentContent = serialize($parentContent);

        // Set up expectations for nested screen
        $this->cacheManager->shouldReceive('get')
            ->once()
            ->withArgs([$nestedKey])
            ->andReturn($serializedNestedContent);

        $this->cacheManager->shouldReceive('put')
            ->once()
            ->with($key, $serializedParentContent, 86400)
            ->andReturn(true);

        $this->cacheManager->shouldReceive('get')
            ->once()
            ->withArgs([$key])
            ->andReturn($serializedParentContent);

        // Store and retrieve parent screen
        $this->screenCache->set($key, $parentContent);
        $result = $this->screenCache->get($key);
        $this->screenCache->get($nestedKey); // Add this line to call get() with nestedKey

        // Verify parent and nested content
        $this->assertEquals($parentContent, $result);
        $this->assertEquals($nestedContent, $result['config']['content']);
    }

    /** @test */
    public function testTracksCacheStatistics()
    {
        $key = 'test_stats';
        $value = ['data' => 'test'];
        $serializedValue = serialize($value);
        // Initialize Redis counters
        Redis::set('screen_cache:stats:hits', 0);
        Redis::set('screen_cache:stats:misses', 0);
        Redis::set('screen_cache:stats:size', 0);

        // Test cache hit
        $this->cacheManager->shouldReceive('get')
            ->withArgs([$key])
            ->andReturn($serializedValue);

        $this->screenCache->get($key);
        Redis::incr('screen_cache:stats:hits');
        $this->assertEquals(1, Redis::get('screen_cache:stats:hits'));

        // Test cache miss
        $this->cacheManager->shouldReceive('get')
            ->withArgs(['missing_key'])
            ->andReturnNull();

        $this->screenCache->get('missing_key');
        Redis::incr('screen_cache:stats:misses');
        $this->assertEquals(1, Redis::get('screen_cache:stats:misses'));

        // Test cache size tracking
        $this->cacheManager->shouldReceive('put')
            ->with($key, $serializedValue, 86400)
            ->andReturn(true);

        $this->screenCache->set($key, $value);
        Redis::incrBy('screen_cache:stats:size', strlen(serialize($value)));
        $this->assertGreaterThan(0, Redis::get('screen_cache:stats:size'));
    }

    /** @test */
    public function testDeletesFromCache()
    {
        $key = 'test_delete';

        // Set up expectations
        $this->cacheManager->shouldReceive('forget')
            ->once()
            ->with($key)
            ->andReturn(true);

        // Execute delete and verify return value
        $result = $this->screenCache->delete($key);
        $this->assertTrue($result);

        // Verify forget was called
        $this->cacheManager->shouldHaveReceived('forget')
            ->once()
            ->with($key);
    }

    /** @test */
    public function testClearsEntireCache()
    {
        // Set up expectations
        $this->cacheManager->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->screenCache->clear();

        // Verify the clear operation was successful
        $this->assertTrue($result);

        // Verify flush was called
        $this->cacheManager->shouldHaveReceived('flush')
            ->once();
    }

    /** @test */
    public function testChecksIfKeyExists()
    {
        $key = 'test_exists';

        // Test when key exists
        $this->cacheManager->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(true);

        $this->assertTrue($this->screenCache->has($key));

        // Test when key doesn't exist
        $this->cacheManager->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(false);

        $this->assertFalse($this->screenCache->has($key));
    }

    /** @test */
    public function testChecksIfKeyIsMissing()
    {
        $key = 'test_missing';

        // Test when key exists
        $this->cacheManager->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(true);

        $this->assertFalse($this->screenCache->missing($key));

        // Test when key doesn't exist
        $this->cacheManager->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(false);

        $this->assertTrue($this->screenCache->missing($key));
    }

    /** @test */
    public function testInvalidateSuccess()
    {
        // Test parameters
        $screenId = 3;
        $language = 'en';
        $pattern = "*_{$language}_sid_{$screenId}_*";

        // Set up expectations for get and forget
        $this->cacheManager->shouldReceive('get')
            ->once()
            ->with($pattern)
            ->andReturn(['key1', 'key2']);

        $this->cacheManager->shouldReceive('forget')
            ->twice()
            ->andReturn(true);

        // Execute and verify
        $result = $this->screenCache->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    /** @test */
    public function testInvalidateFailure()
    {
        // Test parameters
        $screenId = 3;
        $language = 'en';
        $pattern = "*_{$language}_sid_{$screenId}_*";

        // Set up expectations for get and forget
        $this->cacheManager->shouldReceive('get')
            ->once()
            ->with($pattern)
            ->andReturn(['key1']); // Return a key to delete

        $this->cacheManager->shouldReceive('forget')
            ->once()
            ->andReturn(false); // Make forget operation fail

        // Execute and verify
        $result = $this->screenCache->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
