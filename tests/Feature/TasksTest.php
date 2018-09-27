<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
// use ProcessMaker\Models\ProcessRequest;
// use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\User;
// use Tests\Feature\Shared\ResourceAssertionsTrait;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class TasksTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    const TASKS_URL = '/tasks';

    protected $structure = [
        'uuid',
        'updated_at',
        'created_at',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }
    

    public function testIndex() {
        $response = $this->webGet(self::TASKS_URL, []); //is the path correct?
        $response->assertStatus(200);
        $response->assertViewIs('tasks.index'); // is the view correct? this is set within the Controller
        $response->assertSee('class="container" id="tasks"'); // does the string show within the view?
    }

    public function testEdit()
    {
      $tasks_uuid = factory(User::class)->create()->uuid_text;
      $response = $this->webGet('tasks/'.$tasks_uuid . '/edit');
    //   $response->assertStatus(200);
      $response->assertViewIs('tasks.edit');
    }

    // public function testCreateRoute()
    // {

    //   // get the URL
    //   $response = $this->apiCall('GET', '/admin/users/create');

    //   $response->assertStatus(200);
    //   // check the correct view is called
    //   $response->assertViewIs('admin.users.create');

    // }
}
