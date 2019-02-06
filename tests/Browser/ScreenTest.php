<?php
namespace Tests\Browser;

use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class ScreenTest extends DuskTestCase
{
    /**
     * Screen builder test
     */
    public function testScreen()
    {
        $this->browse(function (Browser $browser) {

            //Login
            $browser->visit(new Login())
                ->type('@username', 'admin')
                ->type('@password', 'admin')
                ->press('@login');

            //display list screens
            $browser->clickLink('Processes')
                ->waitForText('PROCESS')
                ->click('a[title=Screens]')
                ->waitFor('#screenIndex')
                ->waitFor('.data-table');

            //display modal and Create new screen
            $browser->click('button[data-target=\\#createScreen]')
                ->waitFor('#createScreen')
                ->type('title', 'New Screen Test')
                ->select('type', 'FORM')
                ->type('description', 'Screen created for test')
                ->click('.modal-footer .btn.btn-success.ml-2')
                ->waitFor('#screen-container')
                ->pause(2500);
        });
    }
}
