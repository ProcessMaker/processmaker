<?php

namespace Tests\Browser;

use Illuminate\Support\Facades\Hash;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
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
                //Check titles of columns
                ->assertSee('Name')
                ->assertSee('Description')
                ->assertSee('Type')
                ->assertSee('Modified')
                ->assertSee('Created');
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
                //Fill form data
                ->type('title', 'New Screen Test')
                ->select('type', 'FORM')
                ->type('description', 'Screen created for test')
                ->click('#createScreen .modal-footer .btn.btn-secondary')
                //save successfully and redirect
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

            // We create a screen with configuration
            $json = json_decode(file_get_contents(base_path('tests/Browser/Assets/controlsHide.json')));
            $screen = factory(Screen::class)->create([
                'title' => 'Test form hide controls',
                'config' => $json
            ]);

            //display form builder, show or hide controls
            $browser->visit('/processes/screen-builder/' . $screen->id . '/edit')
                //wait for Editor screens
                ->waitFor('#screen-container')
                ->assertSee('Controls')
                ->assertSee('Inspector')
                //change to Screen Preview
                ->click('.navbar-nav.mr-auto:nth-child(1n) li:nth-child(2n)')
                ->waitForText('Data Input')
                ->assertSee('Data Input')
                //Check all fields are displayed
                ->assertSee('Field 1')
                //by default fields are hidden
                ->assertDontSee('New Text')
                ->assertDontSee('New Input')
                ->assertDontSee('New Select')
                ->assertDontSee('New Checkbox')
                ->assertDontSee('New TextArea')
                ->assertDontSee('New Radio Button Group')
                ->assertDontSee('New Option')
                ->assertDontSee('New Date Picker')
                ->assertDontSee('NEW PAGE NAVIGATION')
                ->assertDontSee('NEW SUBMIT')
                ->assertDontSee('New File Upload')
                //->assertDontSee('New File Download')
                //change the value to evaluate to "field1 == 'test'" and all fields must be visible
                ->type('#renderer-container input[name=field1]', 'test')
                ->assertSee('Field 1')
                ->assertSee('New Text')
                ->assertSee('New Input')
                ->assertSee('New Select')
                ->assertSee('New Checkbox')
                ->assertSee('New TextArea')
                ->assertSee('New Radio Button Group')
                ->assertSee('New Option')
                ->assertSee('New Date Picker')
                ->assertSee('NEW PAGE NAVIGATION')
                ->assertSee('NEW SUBMIT')
                ->assertSee('New File Upload')
                //->assertSee('New File Download')
                ;

            //Accept leave page
            $browser->visit('/processes/screens')
                ->acceptDialog();
        });
    }

    /**
     * The user has permission to export in Screen builder
     * @throws \Throwable
     */
    public function testPermissionExportScreenBuilder()
    {
        $this->browse(function (Browser $browser) {

            // We create a screen with configuration
            $json = json_decode(file_get_contents(base_path('tests/Browser/Assets/controlsHide.json')));
            $screen = factory(Screen::class)->create([
                'title' => 'Test form hide controls',
                'config' => $json
            ]);

            //display form builder, show or hide controls
            $browser->visit('/processes/screen-builder/' . $screen->id . '/edit')
                //wait for Editor screens
                ->waitFor('#screen-container')
                ->assertSee('Controls')
                ->assertSee('Inspector')
                //display link for export
                ->waitFor('.fa-file-export');
        });

    }

    /**
     * The user does not have permission to export screen.
     * @throws \Throwable
     */
    public function testWithoutPermissionExportScreenBuilder()
    {
        $this->browse(function (Browser $browser) {

            $browser->visit('/logout');

            $user = factory(User::class)->create([
                    'username' => 'standard',
                    'password' => Hash::make('admin'),
                    'status' => 'ACTIVE',
                    'is_administrator' => false
            ]);

            $user->giveDirectPermission('edit-screens');

            //Login
            $browser->visit(new Login())
                ->type('@username', 'standard')
                ->type('@password', 'admin')
                ->press('@login');

            // We create a screen with configuration
            $json = json_decode(file_get_contents(base_path('tests/Browser/Assets/controlsHide.json')));
            $screen = factory(Screen::class)->create([
                'title' => 'Test form hide controls',
                'config' => $json
            ]);

            //display form builder, show or hide controls
            $browser->visit('/processes/screen-builder/' . $screen->id . '/edit')
                //wait for Editor screens
                ->waitFor('#screen-container')
                ->assertSee('Controls')
                ->assertSee('Inspector')
                //display link for export
                ->assertMissing('.fa-file-export');
        });

    }
}
