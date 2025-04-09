<?php

namespace Tests\Repositories;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\Setting;
use Tests\TestCase;

class SettingsConfigRepositoryTest extends TestCase
{
    public function testDotNotation()
    {
        Setting::create([
            'key' => 'test',
            'config' => '{"dot":{"notation":"the value"}}',
            'format' => 'array',
        ]);

        $this->assertEquals('the value', config('test.dot.notation'));
    }
}
