<?php
namespace Tests\Unit\ProcessMaker\Config;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use ProcessMaker\Model\Configuration;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test to ensure that na invalid parameter is returning null
     */
    public function testGetConfigurationValueDoesNotExist()
    {
        $this->assertEquals(null, Config::get('doesnotexist'));
        $this->assertEquals(null, Config::get('test.doesnotexist'));

    }

    /**
     * Tests to ensure that values set in config files have higher priority 
     * 
     */
    public function testConfigurationFilesHavePriority()
    {
        // We will manually add a config to cache, to ensure that the 
        // value from the config files will be fetched instead.
        Cache::forever('config:app', json_encode(['app' => ['name' => 'Test Application']]));
        $this->assertEquals('ProcessMaker', config('app.name'));
    }

    /**
     * Tests to determine if the Config repository has certain keys in memory, then
     * datbase, then cache
     */
    public function testHas()
    {
        // Get app.env, which should be in the memory config
        $this->assertEquals(true, Config::has('app.name'));
        // Now try cache, inserting a mock config property
        Cache::forever('config:test', json_encode([
            'test' => [
                'value' => 'testvalue'
                ]
            ]));
        $this->assertEquals(true, Config::has('test.value'));
        Cache::forget('config:test');
        // Now try database
        Configuration::create([
            'parameter' => 'test2',
            'value' => json_encode(['test2' => [
                'value2' => 'testvalue'
            ]])
        ]);
        $this->assertEquals(true, Config::has('test2.value2'));
        // Now test for no value found
        $this->assertEquals(false, Config::has('invalid.value'));
    }

    /**
     * Test to ensure a matching parameter in the database is returned
     */
    public function testGetWithDatabaseRecord()
    {
         Configuration::create([
            'parameter' => 'test2',
            'value' => json_encode(['test2' => [
                'value2' => 'testvalue'
            ]])
        ]);
        $this->assertEquals('testvalue', Config::get('test2.value2'));
    }

    /**
     * Test to ensure that even if a database record for the config namespace 
     * exists, if no matching parameter is found, null is returned
     */
    public function testGetWithDatabaseRecordButNoMatchingSubValue()
    {
         Configuration::create([
            'parameter' => 'test2',
            'value' => json_encode(['test2' => [
                'value2' => 'testvalue'
            ]])
        ]);
         $this->assertEquals(null, Config::get('test2.invalid'));
    }

    /**
     * Test to ensure that getting a configuration value will result in a value if 
     * it happens to be in cache
     */
    public function testGetWithCacheHit()
    {
        Cache::forever('config:test', json_encode([
            'test' => [
                'value' => 'testcache'
                ]
            ]));
        $this->assertEquals('testcache', Config::get('test.value'));
    }


    /**
     * Test to ensure forgetting configuration values will be removed from cache as well 
     * as database.
     */
    public function testForget()
    {
        Config::set('test.value', 'testvalue');
        Config::forget('test.value');
        $this->assertEquals(null, Cache::get('config:test'));
        // Test to make sure there is no Configuration in model with test namespace
        $this->assertEquals(0, Configuration::where('parameter', 'test')->count());
        // Test to ensure no cache entry exists
        $this->assertEquals(null, Cache::get('config:test'));
        // Now test to make sure there's still an item in db/cache if a sibling value is forgotten
        Config::set('test.value', 'testvalue');
        Config::set('test.sibling', 'testsibling');
        Config::forget('test.value');
        $this->assertArraySubset([
            'test' => [
                'sibling' => 'testsibling'
            ]
            ], json_decode(Cache::get('config:test'), true));
        $this->assertArrayNotHasKey('value', (json_decode(Cache::get('config:test'), true))['test']);
    }

    /**
     * Test to ensure that getMany works with database config values stored
     */
    public function testGetMany()
    {
         Configuration::create([
            'parameter' => 'taylor',
            'value' => json_encode(['taylor' => [
                'test' => 'testresult',
                'notqueried' => 'test'
            ]])
        ]);
 
        $this->assertEquals([
            'test' => null,
            'taylor.test' => 'testresult',
            'taylor.value' => null
        ], Config::getMany(['test', 'taylor.test', 'taylor.value']));

    }

}
