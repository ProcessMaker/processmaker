<?php

namespace Tests\Feature\Api;

use Auth;
use ProcessMaker\Models\User;
use Tests\TestCase;

class DesignerControllerTest extends TestCase
{
    /**
     * A basic test.
     *
     * @return void
     */
    public function testIndexMethod()
    {
        $user = User::factory()->create([
            'is_administrator' => true,
        ]);
        Auth::login($user);
        $response = $this->get('/designer');

        $response->assertStatus(200);
        $response->assertViewIs('designer.index');
    }
}
