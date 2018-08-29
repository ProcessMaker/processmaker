<?php
namespace Tests\Feature\Api\Requests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Application;
use ProcessMaker\Model\User;
use Tests\TestCase;

class VariablesTest extends TestCase
{
    use DatabaseTransactions;


    /**
     * Test to determine fetching variables for a case/application.  Checks for proper policy check
     * and then that we're receiving the application data json.
     */
    public function testVariablesGet()
    {

      $this->markTestSkipped('Access control via permissions and roles removed');

        $user = factory(User::class)->create([
            'password' => Hash::make('password')
        ]);
        // We need an application
        $application = factory(Application::class)->create();

        $response = $this->actingAs($user, 'api')->json('GET', '/api/1.0/requests/' . $application->uid . '/variables');

        // We will first get a 403, because we are not a process supervisor and we haven't participated
        $response->assertStatus(403);

        // Let's set ourselves up as a supervisor
        $application->process->addUserSupervisor($user);

        // Now, let's fetch.  If we're a supervisor, we should be good to go
        $response = $this->actingAs($user, 'api')->json('GET', '/api/1.0/requests/' . $application->uid . '/variables');
        // Now make sure we pass policy and that we get the app data
        $response->assertStatus(200);
    }

}
