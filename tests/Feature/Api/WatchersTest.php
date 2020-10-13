<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Support\Facades\Notification;
use ProcessMaker\Notifications\ScriptResponseNotification;

/**
 *
 * @group process_tests
 */
class WatchersTest extends TestCase
{
    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    const API_TEST_URL = 'api.scripts.execute';

    /**
     * Test watcher calling script
     */
    public function testExecuteWatcherScript()
    {
        Notification::fake();
        ScriptExecutor::setTestConfig('php');
        $script = factory(Script::class)->create([
            'language' => 'PHP',
            'code' => '<?php return ["language"=>"PHP","data"=>$data,"config"=>$config];',
        ]);
        $watcher = uniqid();
        $data = ['a' => 1];
        $config = ['c' => 'complete'];
        $request = $this->apiCall('POST', route(self::API_TEST_URL, [$script]), [
            'watcher' => $watcher,
            'data' => json_encode($data),
            'config' => json_encode($config),
        ]);
        $response = $request->json();
        $this->assertArraySubset(['status' => 'success'], $response);
        Notification::assertSentTo(
            [$this->user], ScriptResponseNotification::class
        );
    }
}
