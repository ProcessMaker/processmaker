<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Browser\Pages\LoginPage;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Browser\Pages\RequestsPage;
use DatabaseSeeder;

class RequestsTest extends DuskTestCase
{

    use DatabaseMigrations;

    public $user;

    protected function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create([
            'username' => 'testuser',
            'password' => Hash::make('secret'),
            'status' => 'ACTIVE'
        ]);
    }


    // public function test_request_route_protected()
    // {
    //     $this->browse(function (Browser $browser) {
    //         $browser->visit('/requests')
    //             ->assertPathIs('/login');
    //     });
    // }

    // public function test_request_route_loads()
    // {
    //     $user = $this->user;

    //     $this->browse(function ($first) use ($user) {
    //         $first->loginAs($user)
    //             ->visit(new RequestsPage)
    //             ->assertRouteIs('requests.index')
    //             ->assertSee('My Requests');
    //     });
    // }

    // public function test_pmql_initial_load()
    // {

    //     $user = $this->user;

    //     $this->browse(function ($first) use ($user) {
    //         $first->loginAs($user)
    //             ->visit(new RequestsPage)
    //             ->assertVueContains('pmql', '(status = "In Progress") AND (requester = "testuser")', '#requests-listing');
    //     });
    // }

    // public function test_vuetable_initial_load()
    // {
    //     // Initial load of the site would have no requests started
    //     $user = $this->user;

    //     $this->browse(function ($first) use ($user) {
    //         $first->loginAs($user)
    //             ->visit(new RequestsPage)
    //             ->waitUntilMissing('.vuetable')
    //             ->assertVue('data', '', '@container');
    //     });
    // }

    public function test_start_request()
    {
        // Add admin user for seeder
        // factory(User::class)->create([
        //     'username' => 'admin',
        //     'password' => Hash::make('admin'),
        //     'language' => 'en',
        //     'status' => 'ACTIVE',
        //     'is_administrator' => true,
        // ]);
        // Add a request to the database
        // $this->seed(\ProcessSeeder::class);


        // Run the test request
        $user = $this->user;

        $this->browse(function ($first) use ($user) {
            $first->loginAs($user)
                ->visit(new RequestsPage)
                ->waitFor('#navbar')
                ->click("#navbar-request-button")
                ->whenAvailable('#requests-modal', function ($modal) {
                    $modal->assertSee("New Request")
                        ->waitFor(".no-requests")
                        ->assertSee("You don't have any Processes.");
                });
        });
    }
}
