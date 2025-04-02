<?php

namespace Tests\Unit\ProcessMaker\Cache\Screens;

use Mockery;
use ProcessMaker\Cache\Screens\LegacyScreenCacheAdapter;
use ProcessMaker\Managers\ScreenCompiledManager;
use Tests\TestCase;

class LegacyScreenCacheAdapterTest extends TestCase
{
    protected $compiledManager;

    protected $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiledManager = Mockery::mock(ScreenCompiledManager::class);
        $this->adapter = new LegacyScreenCacheAdapter($this->compiledManager);
    }

    /** @test */
    public function test_it_creates_correct_cache_key()
    {
        $this->compiledManager->shouldReceive('createKey')
            ->once()
            ->with('1', '2', 'en', '3', '4')
            ->andReturn('pid_1_2_en_sid_3_4');

        $key = $this->adapter->createKey([
            'process_id' => 1,
            'process_version_id' => 2,
            'language' => 'en',
            'screen_id' => 3,
            'screen_version_id' => 4,
        ]);

        $this->assertEquals('pid_1_2_en_sid_3_4', $key);
    }

    /** @test */
    public function test_it_throws_exception_when_missing_required_parameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters for screen cache key');

        $this->adapter->createKey([
            'process_id' => 1,
            // Missing process_version_id
            'language' => 'en',
            'screen_id' => 3,
            'screen_version_id' => 4,
        ]);
    }

    /** @test */
    public function test_it_gets_content_from_compiled_manager()
    {
        $key = 'test_key';
        $expectedValue = ['content' => 'test'];

        $this->compiledManager->shouldReceive('getCompiledContent')
            ->once()
            ->with($key)
            ->andReturn($expectedValue);

        $result = $this->adapter->get($key);

        $this->assertEquals($expectedValue, $result);
    }

    /** @test */
    public function test_it_returns_default_value_when_content_missing()
    {
        $key = 'missing_key';
        $default = ['default' => 'value'];

        $this->compiledManager->shouldReceive('getCompiledContent')
            ->once()
            ->with($key)
            ->andReturnNull();

        $result = $this->adapter->get($key, $default);

        $this->assertEquals($default, $result);
    }

    /** @test */
    public function test_it_stores_content_in_compiled_manager()
    {
        $key = 'test_key';
        $value = ['content' => 'test'];

        $this->compiledManager->shouldReceive('storeCompiledContent')
            ->once()
            ->with($key, $value)
            ->andReturnNull();

        $result = $this->adapter->set($key, $value);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_it_checks_existence_in_compiled_manager()
    {
        $key = 'test_key';

        $this->compiledManager->shouldReceive('getCompiledContent')
            ->once()
            ->with($key)
            ->andReturn(['content' => 'exists']);

        $result = $this->adapter->has($key);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_it_returns_false_when_checking_missing_content()
    {
        $key = 'missing_key';

        $this->compiledManager->shouldReceive('getCompiledContent')
            ->once()
            ->with($key)
            ->andReturnNull();

        $result = $this->adapter->has($key);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_it_invalidates_successfully()
    {
        // Test parameters
        $screenId = 5;
        $language = 'es';

        // Setup expectations
        $this->compiledManager->shouldReceive('deleteScreenCompiledContent')
            ->once()
            ->with($screenId, $language)
            ->andReturn(true);

        // Execute and verify
        $result = $this->adapter->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    /** @test */
    public function test_it_invalidates_with_failure()
    {
        // Test parameters
        $screenId = 5;
        $language = 'es';

        // Setup expectations for failure
        $this->compiledManager->shouldReceive('deleteScreenCompiledContent')
            ->once()
            ->with($screenId, $language)
            ->andReturn(false);

        // Execute and verify
        $result = $this->adapter->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    /** @test */
    public function testInvalidateWithSpecialLanguageCode()
    {
        // Test parameters with special language code
        $screenId = 5;
        $language = 'zh-CN';

        // Setup expectations
        $this->compiledManager->shouldReceive('deleteScreenCompiledContent')
            ->once()
            ->with($screenId, $language)
            ->andReturn(true);

        // Execute and verify
        $result = $this->adapter->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    /** @test */
    public function testInvalidateWithEmptyResults()
    {
        // Test parameters
        $screenId = 999; // Non-existent screen ID
        $language = 'es';

        // Setup expectations for no files found
        $this->compiledManager->shouldReceive('deleteScreenCompiledContent')
            ->once()
            ->with($screenId, $language)
            ->andReturn(false);

        // Execute and verify
        $result = $this->adapter->invalidate(['screen_id' => $screenId, 'language' => $language]);
        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
