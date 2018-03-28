<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    /**
     * A basic cache example.
     *
     * @return void
     */
    public function testCache()
    {
        Cache::put('foo', 'bar', 5);
        $this->assertEquals('bar', (Cache::get('foo')));
    }
}
