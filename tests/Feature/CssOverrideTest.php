<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CssOverrideTest extends TestCase
{
    use RequestHelper;

    /**
     * @test
     */
    public function a_user_can_view_css_override()
    {
        // get the URL
        $response = $this->webcall('GET', route('customize-ui.edit'));

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.cssOverride.edit');
    }
}
