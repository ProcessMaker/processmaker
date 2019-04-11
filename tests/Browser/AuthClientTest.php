<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class AuthClientTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testAuthClientCreation()
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
                ->waitUntilMissing(".vuetable-empty-result")
            //Add Auth Client
                ->press(".fa-key");
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='authClients']/div[2]/div[1]/div/button"))
                ->click();  //The add button lacks a unique ID
//                ->press(".btn-secondary") //We can use this line and remove the previous two once the add button is updated
            $browser->type("#name", "foobar")
                ->type("#redirect", "https://foo.bar.com")
                ->press(".ml-2")
                ->pause(500)   //No choice here, we have to pause for either the error message or the success alert.
                ->assertMissing(".invalid-feedback")
                ->waitForText('foobar', 10);
            //Edit Auth Client
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='authClients']/div[2]/div[2]/div/table/tbody/tr[1]/td[5]/div/div/button[1]/i"))
                ->click();  //The edit button lacks a unique ID
            $browser->pause(500)
                ->assertSee('Edit Auth Client')
                ->type("#name", "bar foo")
                ->type("#redirect", "https://bar.foo.com")
                ->press(".ml-2")
                ->pause(500)   //No choice here, we have to pause for either the error message or the success alert.
                ->assertMissing(".invalid-feedback")
                ->waitForText('bar foo', 10);
            //Delete Auth Client
            $browser->driver->findElement(WebDriverBy::xpath("//*[@id='authClients']/div[2]/div[2]/div/table/tbody/tr[1]/td[5]/div/div/button[2]/i"))
                ->click();  //The delete button lacks a unique ID
            $browser->waitFor('#confirmModal', 10)
                ->press("#confirm")
                ->pause(750)   //No choice here, we have to pause for either the error message or the success alert.
                ->assertDontSee("bar foo");
        });

    }
}
