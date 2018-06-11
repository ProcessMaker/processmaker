<?php

namespace Tests\Browser\Pages\Auth;

use Tests\Browser\Pages\Page;
use Laravel\Dusk\Browser;

class Login extends Page
{

    public function url()
    {
        return '/login';
    }

    public function submitLogin(Browser $browser, $username, $password)
    {
        $browser->type('username', $username);
        $browser->type('password', $password);
        $browser->press('LOGIN');
    }

}