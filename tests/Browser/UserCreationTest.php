<?php

namespace Tests\Browser;

use Barryvdh\Debugbar\Middleware\DebugbarEnabled;
use DebugBar\DebugBar;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class UserCreationTest extends DuskTestCase
{
    /**
     * @throws \Throwable
     */
    public function testUserCreation()
    {
        //Factory 100 users
        Artisan::call('migrate:fresh', []);
        $user = factory(User::class)->create([
            'username' => 'admin',
            'password' => 'admin',
            'email' => 'any@gmail.com',
            'firstname' => 'admin',
            'lastname' => 'admin',
            'timezone' => null,
            'datetime_format' => null,
            'status' => 'ACTIVE',
            'is_administrator' => true,
        ]);
        factory(User::class, 99)->create();


    }
}
