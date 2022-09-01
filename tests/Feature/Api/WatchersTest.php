<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use ProcessMaker\Events\ScriptResponseEvent;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use Tests\Feature\Shared\RequestHelper;
use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;

/**
 * @group process_tests
 */
class WatchersTest extends TestCase
{
    use WithFaker;
    use ResourceAssertionsTrait;
    use RequestHelper;

    const API_TEST_URL = 'api.scripts.execute';

    public function setUpWithPersonalAccessClient()
    {
        $this->withPersonalAccessClient();
    }

    /**
     * Test watcher calling script
     */
    public function testExecuteWatcherScript()
    {
        Event::fake([
            ScriptResponseEvent::class,
        ]);
        $script = factory(Script::class)->create([
            'language' => 'PHP',
            'code' => '<?php return ["language"=>"PHP","data"=>$data,"config"=>$config];',
            'run_as_user_id' => $this->user->id,
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
        Event::assertDispatched(ScriptResponseEvent::class, function ($event) use ($data, $config) {
            $response = $event->response;

            return $response['output'] == ['language'=>'PHP', 'data'=>$data, 'config'=>$config];
        });
    }
}
