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
    
    public function testBasicExample()
    {
        $user = factory(User::class)->create([
            'email' => 'taylor@laravel.com',
        ]);

        $this->browse(function ($browser) use ($user) {
            $browser->visit('/login')
                    ->type('email', $user->email)
                    ->type('password', 'secret')
                    ->press('Login')
                    ->assertPathIs('/home');
        });
    }    
    /**
     * @throws \Throwable
     
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
            $browser->visit('/')
                ->assertSee('Username')
                ->type('#username', 'admin')
                ->type('#password', 'admin')
                ->press('.btn')
                ->clickLink('Admin')
                //Visit /admin/users
                ->pause(5000)
                ->waitFor('.vuetable-body', 5)
                //Verify we see 1 - 10 of 100 Users on the page
                ->assertSee('1 - 10 of 100 Users')
                //Click on the navigation for page 2
                ->click('div.icon:nth-child(8)')
                ->pause(1000)
                //Verify we see 11 - 20 of 100 Users on the page
                ->assertSee('11 - 20 of 100 Users')
                //Verify we see next/previous button
                ->assertPresent('div.icon:nth-child(2) > .fa-angle-left')
                ->assertPresent('div.icon:nth-child(8) > .fa-angle-right');
        });
    }        
        */

}
