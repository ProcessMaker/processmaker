<?php

namespace Tests\Feature\Cache;

use Tests\TestCase;

class SettingCacheManagerTest extends TestCase
{
    public function testGet()
    {
        $key = 'test_key';
        $default = 'default_value';
        $expected = 'cached_value';

        \SettingCache::shouldReceive('get')
            ->with($key, $default)
            ->andReturn($expected);

        $result = \SettingCache::get($key, $default);

        $this->assertEquals($expected, $result);
    }

    public function testSet()
    {
        $key = 'test_key';
        $value = 'test_value';
        $ttl = 60;

        \SettingCache::shouldReceive('set')
            ->with($key, $value, $ttl)
            ->andReturn(true);

        $result = \SettingCache::set($key, $value, $ttl);

        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $key = 'test_key';

        \SettingCache::shouldReceive('delete')
            ->with($key)
            ->andReturn(true);

        $result = \SettingCache::delete($key);

        $this->assertTrue($result);
    }

    public function testClear()
    {
        \SettingCache::shouldReceive('clear')
            ->andReturn(true);

        $result = \SettingCache::clear();

        $this->assertTrue($result);
    }

    public function testHas()
    {
        $key = 'test_key';

        \SettingCache::shouldReceive('has')
            ->with($key)
            ->andReturn(true);

        $result = \SettingCache::has($key);

        $this->assertTrue($result);
    }

    public function testMissing()
    {
        $key = 'test_key';

        \SettingCache::shouldReceive('missing')
            ->with($key)
            ->andReturn(false);

        $result = \SettingCache::missing($key);

        $this->assertFalse($result);
    }

    public function testCall()
    {
        $method = 'add';
        $arguments = ['arg1', 'arg2'];
        $expected = 'cached_value';

        \SettingCache::shouldReceive($method)
            ->with(...$arguments)
            ->andReturn($expected);

        $result = \SettingCache::__call($method, $arguments);

        $this->assertEquals($expected, $result);
    }
}
