<?php
namespace Tests\Feature\Middleware;

use ProcessMaker\Http\Middleware\SetSkin;
use Tests\TestCase;
use Route;
use Igaster\LaravelTheme\Facades\Theme;

class SetSkinMiddlewareTest extends TestCase
{

    /**
     * Test to ensure that passing in a skin attribute sets the skin property of the view config
     * in the application.
     */
    public function testSkinProperlySet()
    {
        Route::get('/_tests/{skin}/test', function() {
            // Print out our current locale
            return Theme::get();
        })->middleware(SetSkin::class);

        // Test chinese (singapore)
        $response = $this->get('_tests/testskin/test');
        $response->assertStatus(200);
        $response->assertSee('testskin');
    }

}