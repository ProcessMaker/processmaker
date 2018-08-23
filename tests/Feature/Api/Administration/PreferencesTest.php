<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Model\User;
use Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Role;
use Illuminate\Support\Facades\Auth;


class PreferencesTest extends TestCase
{
    /**
     * Test that a user can see the preferences page
     *
     */
    public function testPreferencesRoute()
    {
         Auth::login(User::first());

        $response = $this->get('/admin/preferences');

        $response->assertStatus(200);
    }
}
