<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\User;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class CssOverrideTest extends TestCase
{
    use RequestHelper;

    #[Test]
    public function a_user_can_view_css_override(): void
    {
        // get the URL
        $response = $this->webcall('GET', route('customize-ui.edit'));

        $response->assertStatus(200);
        // check the correct view is called
        $response->assertViewIs('admin.cssOverride.edit');
    }
}
