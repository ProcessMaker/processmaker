<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use ProcessMaker\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;

class ProfileTest extends DuskTestCase
{

    use withFaker;

    public function test_profile_page_works()
    { 

        $this->browse(function (Browser $browser,$user) {
            $browser->loginAs(User::find(1))
                    ->visit('/profile/1')
                    ->assertSee(User::find(1)->email)
                    ->logout();
        });
    }

    public function test_profile_edit_works()
    {

        $data = [
            'firstname' => $this->faker->firstName
        ];

        $this->browse(function (Browser $browser) use ($data) {
            $browser->loginAs(User::find(1))
                    ->visit('/profile/edit')
                    ->assertValue('#firstname', User::find(1)->firstname) // current value works
                    ->type("#firstname",$data['firstname'])
                    ->click('#save_profile')
                    ->waitFor('.alert-wrapper')
                    ->assertSee('Your profile was saved.')
                    ->visit('/profile/edit')
                    ->assertValue('#firstname',$data['firstname']) // new value works
                    ->logout();
        });        
    }
    
}
