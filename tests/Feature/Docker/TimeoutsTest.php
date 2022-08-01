<?php

namespace Tests\Feature\Docker;

use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Exception\ScriptTimeoutException;
use ProcessMaker\Models\Script;
use Tests\Feature\Shared\BenchmarkHelper;
use Tests\Feature\Shared\LoggingHelper;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

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
     * Make sure we have a personal access client set up
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
        Event::fake([
            ScriptResponseEvent::class,
        ]);
        $this->assertLogIsEmpty();

        $url = route(
            'api.scripts.preview',
            $this->getScript($data['language'], $data['timeout'])->id
        );

        $this->benchmarkStart();
        $response = $this->apiCall('POST', $url, ['code' => '', 'data' => '']);
        $this->benchmarkEnd();

        $this->assertLogMessageExists('Script timed out');
        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());

        // Assertion: An exception is notified to usr through broadcast channel
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;

            return $response['exception'] === ScriptTimeoutException::class;
        });
    }

    /**
     * Run a test script and assert that the specified timeout is not exceeded
     */
    private function assertTimeoutNotExceeded($data)
    {
        Event::fake([
            ScriptResponseEvent::class,
        ]);
        $this->benchmarkStart();
        $url = route(
            'api.scripts.preview',
            $this->getScript($data['language'], $data['timeout'])->id
        );
        $response = $this->apiCall('POST', $url, ['code' => '', 'data' => '']);
        $this->benchmarkEnd();

        $this->assertLessThan(intval($data['timeout']) + 2, $this->benchmark());
        $response->assertStatus(200);

        // Assertion: The script output is sent to usr through broadcast channel
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) {
            $response = $event->response;

            return ! array_key_exists('exception', $response);
        });
    }

    /**
     * Test to ensure PHP scripts timeout
     */
    public function testPhpScriptTimeoutExceeded()
    {
        config(['simulate_timeout' => true]);
        $this->assertTimeoutExceeded([
            'language' => 'php',
            'timeout' => self::TIMEOUT_LENGTH,
        ]);
    }

    /**
     * Test to ensure PHP scripts do not timeout if they do not exceed limits
     */
    public function testPhpScriptTimeoutNotExceeded()
    {
        $this->assertTimeoutNotExceeded([
            'language' => 'php',
            'timeout' => self::TIMEOUT_LENGTH,
        ]);
    }

    /**
     * A helper method to generate a script object from the factory
     *
     * @param  string  $language
     * @param  int  $timeout
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
