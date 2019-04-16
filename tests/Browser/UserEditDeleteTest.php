<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class UserAddEditDeleteTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testUserAddEditDelete()
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
            $browser->visit("/")
                ->assertSee("Username")
                ->type("#username", "admin")
                ->type("#password", "admin")
                ->press(".btn")
                ->assertMissing(".invalid-feedback")
                ->clickLink('Admin')
                ->waitUntilMissing(".vuetable-empty-result");
            //Add User
            $browser->press("#addUserBtn")
                ->type("#username", "user1")
                ->type("#firstname", "user1")
                ->type("#lastname", "last1")
                ->select("select[name='size']", "ACTIVE")
                ->type("#email", "user1@hotmail.com")
                ->type("#password", "password123")
                ->type("#confpassword", "password123");
            $browser->whenAvailable(".modal-footer", function ($modal) { //A funky work-around to let me click the save modal button 
                $modal->press(".ml-2");
            })
                ->pause(750)   //No choice here, we have to pause here to wait for either the error message or the success alert.
                ->assertMissing(".invalid-feedback")
                ->waitFor(".alert-dismissible", 10)
                ->assertSee("successfully created");
            //Edit User
            $browser->clickLink("Admin")
                ->waitUntilMissing(".vuetable-empty-result");
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='users-listing']/div[2]/div/div/table/tbody/tr[2]/td[7]/div/div/button[1]/i"))
                ->click();  //The edit button lacks a unique ID
            $browser->waitFor("#navbar-request-button",10) //when this loads, the whole page is loaded
                ->type("#firstname","foo")
                ->type("#lastname","bar")
                ->press(".ml-2")
                ->waitFor(".vuetable-body", 10)
                ->waitUntilMissing(".vuetable-empty-result", 10)
                ->assertSee("foo bar");
            //Delete User
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='users-listing']/div[2]/div/div/table/tbody/tr[2]/td[7]/div/div/button[2]/i"))
                ->click();  //The delete button lacks a unique ID
            $browser->waitFor('#confirmModal', 10)
                ->press("#confirm")
                ->waitFor(".alertBox", 10)
                ->assertSee("The user was deleted");
        });

    }
}