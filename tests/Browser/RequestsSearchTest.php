<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;

use ProcessMaker\Models\User;
use ProcessMaker\Models\ProcessRequest;
use Tests\Browser\Pages\RequestsPage;

class RequestsSearchTest extends DuskTestCase
{

    public function testPmqlErrors()
    {
        $user = User::first();

        factory(ProcessRequest::class)->create([
            'name' => 'Some Request',
            'user_id' => $user->id,
            'status' => 'ACTIVE'
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(new RequestsPage)
                ->click("@advanced-search-button") 
                ->waitFor('@pmql')

                ->value('@pmql', '')->type('@pmql', 'foo = "bar"')
                ->click('@search-button')
                ->waitFor('.alert-wrapper div')
                ->assertSeeIn('.alert-wrapper div', "Unknown column 'foo'")

                ->value('@pmql', '')->type('@pmql', 'name = "bar"')
                ->click('@search-button')
                ->waitForText('No Data Available')

                ->value('@pmql', '')->type('@pmql', 'name like "some%"')
                ->click('@search-button')
                ->waitFor('@vuetable td')
                ->assertSeeIn("@vuetable", "In Progress")
                ;
        });
    }
}
