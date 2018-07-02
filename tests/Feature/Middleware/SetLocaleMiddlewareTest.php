<?php
namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Http\Middleware\SetLocale;
use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Route;

class SetLocaleMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test to ensure that passing in a locale into the url sets it
     * in the application.
     */
    public function testLocaleProperlySet()
    {
        Route::get('/_tests/{lang}/test', function() {
            // Print out our current locale
            return App::getLocale();
        })->middleware(SetLocale::class);

        // Test chinese (singapore)
        $response = $this->get('_tests/zh_SG/test');
        $response->assertStatus(200);
        $response->assertSee('zh_SG');
    }

}