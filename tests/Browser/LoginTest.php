<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use ProcessMaker\Models\User;
use Tests\Browser\Pages\LoginPage;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    public function test_login_page_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(new LoginPage)
                ->assertRouteIs('login');
        });
    }

    public function test_login_page_works()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('secret'),
            'status' => 'ACTIVE',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(new LoginPage)
                ->type('@username', $user->username)
                ->type('@password', 'secret')
                ->press('@login')
                ->assertRouteIs('requests.index')
                ->logout();
        });
    }

    public function test_inactive_user_login_fails()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('secret'),
            'status' => 'INACTIVE',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(new LoginPage)
                ->type('username', $user->username)
                ->type('password', 'secret')
                ->assertInputValue('password', 'secret')
                ->press('@login')
                ->assertRouteIs('login');
        });
    }
}
