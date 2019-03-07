<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\BenchmarkHelper;
use Tests\Feature\Shared\LoggingHelper;
use Tests\Feature\Shared\RequestHelper;

class TimeoutsTest extends TestCase
{
    use BenchmarkHelper, LoggingHelper, RequestHelper;

    /**
     * Skip the test if Docker is not installed
     */
    private function skipWithoutDocker()
    {
        if (! file_exists(config('app.bpm_scripts_home')) || ! file_exists(config('app.bpm_scripts_docker'))) {
            return $this->markTestSkipped('This test requires docker');
        }
    }
    
    /**
     * Run a test script and assert that the specified timeout is exceeded
     */
    private function assertTimeoutExceeded($data)
    {
        $this->assertLogIsEmpty();
        
        $url = route('api.script.preview', $data);
        
        $this->benchmarkStart();
        $response = $this->apiCall('POST', $url, []);
        $this->benchmarkEnd();
        
        $this->assertLogMessageExists('Script timed out');
        $this->assertLessThan(intval($data['timeout']) + 1, $this->benchmark());
        $response->assertStatus(500);
    }

    /**
     * Run a test script and assert that the specified timeout is not exceeded
     */
    private function assertTimeoutNotExceeded($data)
    {
        $this->benchmarkStart();
        $url = route('api.script.preview', $data);
        $response = $this->apiCall('POST', $url, []);
        $this->benchmarkEnd();
        
        $this->assertLessThan(intval($data['timeout']) + 1, $this->benchmark());
        $response->assertStatus(200);
        $response->assertJsonStructure(['output' => ['response']]);
    }

    /**
     * Test to ensure Lua scripts timeout
     */
    public function testLuaScriptTimeoutExceeded()
    {
        $this->skipWithoutDocker();
        
        $this->assertTimeoutExceeded([
            'data' => '{}',
            'code' => 'os.execute("sleep 3") return {response=1}',
            'language' => 'lua',
            'timeout' => 2
        ]);
    }
    
    /**
     * Test to ensure Lua scripts do not timeout if they do not exceed limits
     */
    public function testLuaScriptTimeoutNotExceeded()
    {
        $this->skipWithoutDocker();
        
        $this->assertTimeoutNotExceeded([
            'data' => '{}',
            'code' => 'os.execute("sleep 1") return {response=1}',
            'language' => 'lua',
            'timeout' => 2
        ]);
    }
    
    /**
     * Test to ensure PHP scripts timeout
     */
    public function testPhpScriptTimeoutExceeded()
    {
        $this->skipWithoutDocker();
        
        $this->assertTimeoutExceeded([
            'data' => '{}',
            'code' => '<?php sleep(3); return ["response"=>1];',
            'language' => 'php',
            'timeout' => 2
        ]);
    }
    
    /**
     * Test to ensure PHP scripts do not timeout if they do not exceed limits
     */
    public function testPhpScriptTimeoutNotExceeded()
    {
        $this->skipWithoutDocker();
        
        $this->assertTimeoutNotExceeded([
            'data' => '{}',
            'code' => '<?php sleep(1); return ["response"=>1];',
            'language' => 'php',
            'timeout' => 2
        ]);
    }
}
