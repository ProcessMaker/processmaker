<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

class RequestListTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testLogin()
    {
        $this->browse(function ($browser) {
            //Login
            $browser->visit('/')
                ->assertSee('Username')
                ->type('#username', 'admin')
                ->type('#password', 'admin')
                ->press('.btn')
                ->assertMissing('.invalid-feedback');

            $this->browse(function ($browser) {
                $browser->waitUntilMissing('.lds-gear',20)
                    ->assertMissing('.fa-exclamation-triangle')
                    ->assertMissing('.alert-wrapper')
                    ->assertDontSee('timeout of 5000ms exceeded')
                    ->assertDontSee('Error: timeout of 5000ms exceeded')
                    ->assertDontSee('Server Error');
            });
        });
    }

    public function testMyRequests()
    {
        $this->browse(function ($browser) {
            $browser->click ('div.card-deck > div:nth-child(1)');
            $browser->waitUntilMissing('.lds-gear',20)
                ->assertMissing('.fa-exclamation-triangle')
                ->assertMissing('.alert-wrapper')
                ->assertDontSee('timeout of 5000ms exceeded')
                ->assertDontSee('Error: timeout of 5000ms exceeded')
                ->assertDontSee('Server Error');
        });
    }

    public function testInProgress()
    {
        $this->browse(function ($browser) {
            $browser->click ('div.card-deck > div:nth-child(2)');
            $browser->waitUntilMissing('.lds-gear',20)
                ->assertMissing('.fa-exclamation-triangle')
                ->assertMissing('.alert-wrapper')
                ->assertDontSee('timeout of 5000ms exceeded')
                ->assertDontSee('Error: timeout of 5000ms exceeded')
                ->assertDontSee('Server Error');
        });
    }

    public function testCompleted()
    {
        $this->browse(function ($browser) {
            $browser->click ('div.card-deck > div:nth-child(3)');
            $browser->waitUntilMissing('.lds-gear',20)
                ->assertMissing('.fa-exclamation-triangle')
                ->assertMissing('.alert-wrapper')
                ->assertDontSee('timeout of 5000ms exceeded')
                ->assertDontSee('Error: timeout of 5000ms exceeded')
                ->assertDontSee('Server Error');
        });
    }

    public function testAllRequests()
    {
        $this->browse(function ($browser) {
            $browser->click ('div.card-deck > div:nth-child(4)');
            $browser->waitUntilMissing('.lds-gear',20)
                ->assertMissing('.fa-exclamation-triangle')
                ->assertMissing('.alert-wrapper')
                ->assertDontSee('timeout of 5000ms exceeded')
                ->assertDontSee('Error: timeout of 5000ms exceeded')
                ->assertDontSee('Server Error');
        });
    }
}
