<?php
namespace Tests\Browser;

use ProcessMaker\Models\Screen;
use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class ScreenTest extends DuskTestCase
{

    /**
     * Test display List of screens
     */
    public function testDisplayListScreens()
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
                ->waitFor('.data-table')
                ->assertSee('Name');

            //todo more validations
        });
    }

    /**
     * Test display List of screens
     */
    public function testCreateNewScreen()
    {
        $this->browse(function (Browser $browser) {

            //create new screen
            $browser->visit('/processes/screens')
                ->waitFor('#screenIndex')
                ->click('button[data-target=\\#createScreen]')
                ->waitFor('#createScreen')
                ->type('title', 'New Screen Test')
                ->select('type', 'FORM')
                ->type('description', 'Screen created for test')
                ->click('.modal-footer .btn.btn-success.ml-2')
                ->waitFor('#screen-container')
                ->assertSee('Controls')
                ->assertSee('Inspector');

        });
    }
    /**
     * Screen builder test
     */
    public function testHideControls()
    {
        $this->browse(function (Browser $browser) {

            // We create a screen
            $screen = factory(Screen::class)->create();

            //display form builder
            $browser->visit('/processes/screen-builder/' . $screen->id . '/edit')
                ->waitFor('#screen-container')
                ->assertSee('Controls')
                ->assertSee('Inspector')
                ->pause(1000)
                //->press('#controls .control:nth-child(1n)')
                ->drag('#controls .control:nth-child(2n)', '.editor-canvas .container .editor-draggable')
                ->click('.editor-canvas .container .editor-draggable')
                //->press('.editor-canvas .container .editor-draggable')
                ->pause(2000)

                //->press('#controls .control:nth-child(2n)')
                ->drag('#controls .control:nth-child(2n)', '.editor-canvas .container .editor-draggable')
                ->pause(2000)
                //->press('.editor-draggable .control-item:nth-child(1n)')
                //

                ->pause(2500);
        });
    }
}
