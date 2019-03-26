<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class GroupCreationTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testGroupCreation()
    {
        $this->markTestSkipped('Skipping Dusk tests temporarily');

        //Create Admin User
        Artisan::call('migrate:fresh', []);
        $user = factory(User::class)->create([
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'email' => 'any@gmail.com',
            'firstname' => 'admin',
            'lastname' => 'admin',
            'timezone' => null,
            'datetime_format' => null,
            'status' => 'ACTIVE',
            'is_administrator' => true,
        ]);

        $this->browse(function ($browser) {
            //Login
            $browser->visit("https://bpm4.local.processmaker.com")
                ->assertSee("Username")
                ->type("#username", "admin")
                ->type("#password", "admin")
                ->press(".btn")
                ->assertMissing(".invalid-feedback")
                ->clickLink('Admin')
                ->waitUntilMissing(".vuetable-empty-result");
            //Add User Group
            $browser->press(".fa-users")
                ->press(".btn-secondary")
                ->waitFor('#createGroup', 10)
                ->pause(250)
                ->type("#name", "!foobar")
                ->type("#description", "Group for Foo Bar")
                ->press(".ml-2")
                ->pause(800)
                ->assertMissing(".invalid-feedback")
                ->waitFor('#nav-home-tab', 10);
            //Add User to User Group
            $browser->click("#nav-profile-tab")
                ->waitFor(".btn-action", 10)
                ->press(".btn-action")
                ->waitFor("#addUser", 10)
                ->click(".multiselect__select")
                ->pause(2000);  //For some reason, the selector will not immediately populate like it does under normal usage. This is a work-around
            $browser->driver->findElement(WebDriverBy::xpath("//span[text()='admin admin']"))   //To ensure the correct user is chosen
                ->click();
            $browser->whenAvailable(".modal-footer", function ($modal) { //A funky work-around to let me click the save modal button 
                $modal->press(".ml-2");
            });
            $browser->pause(1000)   //No choice.
                ->waitForText('admin admin', 10)
                ->press(".fa-users")
                ->waitUntilMissing(".vuetable-empty-result")
                ->waitForText('!foobar', 10);
            //Edit User Group
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='listGroups']/div[2]/div/div/table/tbody/tr[1]/td[7]/div/div/button[1]/i"))
                ->click();  //The edit button lacks a unique ID
            $browser->assertSee('Edit !foobar')
                ->type("#name", "!bar foo")
                ->type("#description", "Group for Bar Foo")
                ->select('select[name="status"]', 'INACTIVE')
                ->press(".ml-2")
                ->pause(800)   //No choice here, we have to pause for either the error message or the success alert.
                ->assertMissing(".invalid-feedback")
                ->waitForText('!bar foo', 10);
            //Delete User Group
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='listGroups']/div[2]/div/div/table/tbody/tr[1]/td[7]/div/div/button[2]/i"))
                ->click();  //The delete button lacks a unique ID
            $browser->waitFor('#confirmModal', 10)
                ->press("#confirm")
                ->waitFor('.alert-dismissible', 10)
                ->pause(700)
                ->assertDontSee("!bar foo");
        });

    }
}