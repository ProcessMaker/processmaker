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
    public function it_creates_correct_cache_key()
    {
        $this->compiledManager->shouldReceive('createKey')
            ->once()
            ->with('1', '2', 'en', '3', '4')
            ->andReturn('pid_1_2_en_sid_3_4');

        $key = $this->adapter->createKey(1, 2, 'en', 3, 4);

        $this->assertEquals('pid_1_2_en_sid_3_4', $key);
    }

    /** @test */
    public function it_gets_content_from_compiled_manager()
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
    public function it_returns_default_value_when_content_missing()
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
    public function it_stores_content_in_compiled_manager()
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
    public function it_checks_existence_in_compiled_manager()
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
    public function it_returns_false_when_checking_missing_content()
    {
        $key = 'missing_key';

        $this->compiledManager->shouldReceive('getCompiledContent')
            ->once()
            ->with($key)
            ->andReturnNull();

        $result = $this->adapter->has($key);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
