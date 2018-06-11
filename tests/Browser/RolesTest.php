<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Management\Roles;
use Tests\Browser\Pages\Auth\Login;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Traits\DatabaseMigrationsWithSeeds;

class RolesTest extends DuskTestCase
{
    // Wipes database each run
    use DatabaseMigrationsWithSeeds;

    /**
     * Tests that going to the roles page, we'll see our initial three roles
     *
     * @return void
     */
    public function testInitialRoleListings()
    {
        $roles = Role::get();
        $this->browse(function (Browser $browser) use ($roles) {
            $browser->visit(new Login())
                ->submitLogin('admin', 'admin')
                ->on(new Roles());
            // sleep a few seconds to make sure it loads data from the api
            sleep(4);
            // Now ensure we see all our roles
            foreach($roles as $role) {
                // Ensure we see the role's code
                $browser->assertSee($role->code);
            }
        });
    }
} 