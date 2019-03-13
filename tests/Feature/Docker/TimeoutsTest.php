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

    // How long our test scripts should be allowed to run before timing out
    const TIMEOUT_LENGTH = 3;

    // How long to sleep our test scripts that should exceed timeout
    const SLEEP_EXCEED = 6;
    
    // How long to sleep our test scripts that should not exceed timeout
    const SLEEP_NOT_EXCEED = 1;
    
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
     * Make sure we have a personal access token needed to run scripts
     */
    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }
    
    /**
     * Run a test script and assert that the specified timeout is exceeded
     */
    private function assertTimeoutExceeded($data)
    {
        $this->assertLogIsEmpty();
        
        $url = route(
            'api.script.preview',
            $this->getScript($data['language'], $data['timeout'])->id
        );
        
        $this->benchmarkStart();
        $response = $this->apiCall('POST', $url, $data);
        $this->benchmarkEnd();
        
        $this->assertLogMessageExists('Script timed out');
        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());
        $response->assertStatus(500);
    }

    /**
     * Run a test script and assert that the specified timeout is not exceeded
     */
    private function assertTimeoutNotExceeded($data)
    {
        $this->benchmarkStart();
        $url = route(
            'api.script.preview',
            $this->getScript($data['language'], $data['timeout'])->id
        );
        $response = $this->apiCall('POST', $url, $data);
        $this->benchmarkEnd();
        
        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());
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
            'code' => 'os.execute("sleep ' . self::SLEEP_EXCEED . '") return {response=1}',
            'language' => 'lua',
            'timeout' => self::TIMEOUT_LENGTH
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
            'code' => 'os.execute("sleep ' . self::SLEEP_NOT_EXCEED . '") return {response=1}',
            'language' => 'lua',
            'timeout' => self::TIMEOUT_LENGTH
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
            'code' => '<?php sleep(' . self::SLEEP_EXCEED . '); return ["response"=>1];',
            'language' => 'php',
            'timeout' => self::TIMEOUT_LENGTH
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
            'code' => '<?php sleep(' . self::SLEEP_NOT_EXCEED . '); return ["response"=>1];',
            'language' => 'php',
            'timeout' => self::TIMEOUT_LENGTH
        ]);
    }

    /**
     * A helper method to generate a script object from the factory
     *
     * @param string $language
     * @param integer $timeout
     * @return Script
     */
    private function getScript($language, $timeout)
    {
        return factory(Script::class)->create([
            'run_as_user_id' => $this->user->id,
            'language' => $language,
            'timeout' => $timeout,
        ]);
    }
}
