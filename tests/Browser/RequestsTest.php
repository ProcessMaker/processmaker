<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Browser\Pages\LoginPage;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Browser\Pages\RequestsPage;

class RequestsTest extends DuskTestCase
{

    private function setuser()
    {

        $user = User::where('username', 'testuser')->first();

        if (!$user) {

            $user = factory(User::class)->create([
                'username' => 'testuser',
                'password' => Hash::make('secret'),
                'status' => 'ACTIVE'
            ]);
        }

        return $user;
    }

    public function test_request_route_protected()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/requests')
                ->assertPathIs('/login');
        });
    }

    public function test_request_route_loads()
    {
        $user = $this->setuser();

        $this->browse(function ($first) use ($user) {
            $first->loginAs($user)
                ->visit(new RequestsPage)
                ->assertRouteIs('requests.index')
                ->assertSee('My Requests');
        });
    }

    public function test_pmql_initial_load()
    {

        $user = $this->setuser();

        $this->browse(function ($first) use ($user) {
            $first->loginAs($user)
                ->visit(new RequestsPage)
                ->assertVue('pmql', '(status = "In Progress") AND (requester = "' . $user->username . '")', '#requests-listing');
        });
    }

    public function test_vuetable_initial_load()
    {
        // Initial load of the site would have no requests started
        $user = $this->setuser();

        $this->browse(function ($first) use ($user) {
            $first->loginAs($user)
                ->visit(new RequestsPage)
                ->waitUntilMissing('.vuetable')
                ->assertVue('data', '', '@container');
        });
    }

    public function test_start_request()
    {
        $user = $this->setuser();

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
