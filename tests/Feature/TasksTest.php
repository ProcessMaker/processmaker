<?php
namespace Tests\Feature;

// use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
// use Illuminate\Foundation\Testing\WithFaker;
// use ProcessMaker\Models\ProcessRequest;
// use ProcessMaker\Models\ProcessRequestToken;
// use ProcessMaker\Models\User;
// use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

/**
 * Tests routes related to tokens list and show
 * the creation, update and deletion are controller by the engine
 * and should not be changed by endpoints
 *
 * @group process_tests
 */
class TasksTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    const TASKS_URL = '/tasks';
    const DEFAULT_PASS = 'password';

    protected $structure = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    public function testIndex() {
        $response = $this->webGet(self::TASKS_URL, []);
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index');
        $response->assertSee('class="container" id="tasks"');
    }
}
