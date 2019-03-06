<?php

namespace Tests\Feature\Api;

use Carbon\Carbon;
use Faker\Factory as Faker;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\BenchmarkHelper;
use Tests\Feature\Shared\RequestHelper;

class TimeoutsTest extends TestCase
{
    use BenchmarkHelper, RequestHelper;
    
    /**
     * Run a test script and assert that the specified timeout is exceeded
     */
    private function assertTimeoutExceeded($data)
    {
        $this->benchmarkStart();
        $url = route('api.script.preview', $data);
        $response = $this->apiCall('POST', $url, []);
        $this->benchmarkEnd();
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
     * Test our Lua script timeout functionality
     */
    public function testLuaScriptTimeouts()
    {
        if (!file_exists(config('app.bpm_scripts_home')) || !file_exists(config('app.bpm_scripts_docker'))) {
            $this->markTestSkipped(
                'This test requires docker'
            );
        }
        
        $this->assertTimeoutExceeded(['data' => '{}', 'code' => 'os.execute("sleep 5") return {response=1}', 'language' => 'lua', 'timeout' => 2]);
        $this->assertTimeoutNotExceeded(['data' => '{}', 'code' => 'os.execute("sleep 1") return {response=1}', 'language' => 'lua', 'timeout' => 2]);
    }

    /**
     * Test our PHP script timeout functionality
     */
    public function testPhpScriptTimeouts()
    {
        if (!file_exists(config('app.bpm_scripts_home')) || !file_exists(config('app.bpm_scripts_docker'))) {
            $this->markTestSkipped(
                'This test requires docker'
            );
        }

        $this->assertTimeoutExceeded(['data' => '{}', 'code' => '<?php sleep(5); return ["response"=>1];', 'language' => 'php', 'timeout' => 2]);
        $this->assertTimeoutNotExceeded(['data' => '{}', 'code' => '<?php sleep(1); return ["response"=>1];', 'language' => 'php', 'timeout' => 2]);
    }
}
