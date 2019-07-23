<?php

namespace Tests\Feature;

use ProcessMaker\Models\User;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CssOverrideTest extends TestCase
{
    use RequestHelper;

    /**
     * @test
     */
    public function a_user_can_view_css_override()
    {
        // get the URL
        $response = $this->webcall('GET', route('css.edit'));

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.cssOverride.edit');
    }
}
