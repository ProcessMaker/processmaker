<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class LoginTest extends DuskTestCase
{
    /**
     * Test login
     *
     * @throws \Throwable
     */
    public function testLogin()
    {
        $this->browse(function (Browser $browser) {

            $browser->visit(new Login())
                ->type('@username', 'admin')
                ->type('@password', 'admin')
                ->press('@login')
                ->assertPathIs('/requests');
        });
    }
}
