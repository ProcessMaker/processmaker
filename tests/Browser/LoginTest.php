<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Traits\DatabaseMigrationsWithSeeds;

class ExampleTest extends DuskTestCase
{
    // Wipes database each run
    use DatabaseMigrationsWithSeeds;



    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('LOGIN');
        });
    }
}
