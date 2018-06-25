<?php
namespace Tests\Unit\ProcessMaker\Config;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function testConfigurationValueDoesNotExist()
    {
        $this->expectEquals(null, config('doesnotexist'));
    }

    /**
     * Tests to ensure that values set in config files have higher priority 
     * 
     */
    public function testConfigurationFilesHavePriority()
    {
        // We will manually add a config to cache, to ensure that the 
        // value from the config files will be fetched instead.
        Cache::forever('config:api.name', 'Test Application');
        $this->assertEquals('ProcessMaker', config('app.name'));
    }
}
