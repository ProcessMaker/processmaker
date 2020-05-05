<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Browser\Pages\LoginPage;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends DuskTestCase
{


    public function test_login_page_works()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::find(1))
                    ->visit('/requests')
                    ->assertSee('My Requests');
        });
    }
    
}
