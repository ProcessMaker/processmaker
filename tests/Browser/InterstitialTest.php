<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use ProcessMaker\Models\User;
use Tests\DuskTestCase;
use Tests\Feature\Api\TestProcessExecutionTrait;
use Tests\Feature\Shared\RequestHelper;

class InterstitialTest extends DuskTestCase
{
    use DatabaseMigrations;
    use RequestHelper;
    use TestProcessExecutionTrait;

    protected $withPermissions = false;

    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->user = factory(User::class)->create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'is_administrator' => true,
        ]);
        $this->loadTestProcess(
            file_get_contents(__DIR__ . '/processes/Interstitial.bpmn'),
            [
                '2' => factory(User::class)->create([
                    //'id' => 2,
                    'status' => 'ACTIVE',
                    'is_administrator' => false,
                ])
            ]
        );
        // Start a process request
        $route = route('api.process_events.trigger', [$this->process->id, 'event' => 'node_1']);
        $data = [];
        $response = $this->apiCall('POST', $route, $data);
        $requestJson = $response->json();
        //$request = ProcessRequest::find($requestJson['id']);
        //dump($requestJson);
        // Test
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
            ->type('username', 'admin')
            ->type('password', 'admin')
            ->press('LOG IN')
            ->clickLink('Tasks')
            ->waitForText('Task 1')
            ->clickLink('Task 1')
            ->pause(2000)
            ->press('Complete Task')
            ->assertSee('My Requests1');
        });
    }
}
