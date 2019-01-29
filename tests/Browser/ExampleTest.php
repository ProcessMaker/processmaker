<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use ProcessMaker\Models\User;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testLoginSuccess()
    {
        $user = factory(User::class)->create([
            'username' => 'testymctest',
            'password' => 'testing',
            'status' => 'ACTIVE'
        ]);

        $this->browse(function ($browser) use ($user) {
            $browser->visit('/login')
                    ->type('username', $user->username)
                    ->type('password', 'testing')
                    ->press('LOG IN')
                    ->assertPathIs('/requests');
        });
    }

    public function testLogout()
    {
        $this->browse(function ($browser) {
            $browser->visit('/logout')
                    ->assertPathIs('/login');
        });
    }

    public function testLoginFail()
    {
        $user = factory(User::class)->create([
            'username' => 'testymctest',
            'password' => 'testing',
            'status' => 'ACTIVE'
        ]);

        $this->browse(function ($browser) use ($user) {
            $browser->visit('/login')
                    ->type('username', $user->username)
                    ->type('password', 'fail')
                    ->press('LOG IN')
                    ->assertSee('These credentials do not match our records.');
        });

    }


}
