<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{

    use DatabaseMigrations;

    public $url = '/login';

    /**
     * Assert that the browser is on the page.
     *
     * @param  Browser  $browser
     * @return void
     */
    public function testPageLoad()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit($this->url)
                ->assertSee('Username')
                ->assertSee('Password');
        });
    }

    public function testLogin()
    {
        $this->browse(function (Browser $browser) {

            $browser->visit($this->url)
                ->type('username', 'admin')
                ->type('password', 'admin')
                ->press('login')
                ->assertPathIs('/requests');
        });
    }
}
