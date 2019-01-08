<?php

namespace Tests\Browser;

use Barryvdh\Debugbar\Middleware\DebugbarEnabled;
use DebugBar\DebugBar;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class UserListTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testLogin()
    {
        //Factory 100 users
        Artisan::call('migrate:fresh', []);
        $user = factory(User::class)->create([
            'username' => 'admin',
            'password' => 'admin',
            'email' => 'any@gmail.com',
            'firstname' => 'admin',
            'lastname' => 'admin',
            'timezone' => null,
            'datetime_format' => null,
            'status' => 'ACTIVE',
            'is_administrator' => true,
        ]);
        factory(User::class, 99)->create();
        // Test login
        $this->browse(function (Browser $browser) {

            $browser->resize(1920, 1080);

            $browser->visit('/')
                ->assertSee('Username')
                ->type('#username', 'admin')
                ->type('#password', 'admin')
                ->press('.btn')
                ->clickLink('Admin')
                ->pause(5000)
                ->waitFor('.vuetable-body', 5)
                ->assertSee('1 - 10 of 100 Users');

            $browser->press('#addUserBtn')
                ->type('#username', 'user1')
                ->type('#firstname', 'user1')
                ->type('#lastname', 'last1')
                ->select('select[name="size"]', 'ACTIVE')
                ->type('#email', 'user1@hotmail.com')
                ->type('#password', 'password123')
                ->type('#confpassword', 'password123');

            $browser->maximize();
            $browser->press('.btn.btn-secondary')
                ->pause(5000)
                ->assertSee('successfully created');
        });

    }
}
