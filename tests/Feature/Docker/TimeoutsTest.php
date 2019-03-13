<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\Script;
use Tests\TestCase;
use Tests\Feature\Shared\BenchmarkHelper;
use Tests\Feature\Shared\LoggingHelper;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Notifications\ScriptResponseNotification;
use ProcessMaker\Exception\ScriptTimeoutException;

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
        if (!file_exists(config('app.bpm_scripts_home')) || !file_exists(config('app.bpm_scripts_docker'))) {
            return $this->markTestSkipped('This test requires docker');
        }
    }

    /**
     * Run a test script and assert that the specified timeout is exceeded
     */
    private function assertTimeoutExceeded($data)
    {
        Notification::fake();
        $this->assertLogIsEmpty();

        $url = route('api.script.preview', $data);

        $this->benchmarkStart();
        $response = $this->apiCall('POST', $url, []);
        $this->benchmarkEnd();

        $this->assertLogMessageExists('Script timed out');
        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());

        // Assertion: An exception is notified to usr through broadcast channel
        Notification::assertSentTo(
            [$this->user],
            ScriptResponseNotification::class,
            function ($notification, $channels) {
                $response = $notification->getResponse();
                return $response['exception'] === ScriptTimeoutException::class && in_array('broadcast', $channels);
            }
        );
    }

    /**
     * Run a test script and assert that the specified timeout is not exceeded
     */
    private function assertTimeoutNotExceeded($data)
    {
        Notification::fake();
        $this->benchmarkStart();
        $url = route('api.script.preview', $data);
        $response = $this->apiCall('POST', $url, []);
        $this->benchmarkEnd();

        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());
        $response->assertStatus(200);

        // Assertion: The script output is sent to usr through broadcast channel
        Notification::assertSentTo(
            [$this->user],
            ScriptResponseNotification::class,
            function ($notification, $channels) {
                $response = $notification->getResponse();
                return $response['output'] === ['response' => 1];
            }
        );
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
}
