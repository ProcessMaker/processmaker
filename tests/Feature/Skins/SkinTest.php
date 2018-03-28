<?php
namespace Tests\Feature;

use Router;
use Tests\TestCase;

/**
 * This tests basic skin functionality which includes overriding
 * @package Tests\Feature
 */
class SkinTest extends TestCase
{

    /**
     * Setup our test environment by re-using a common route with the setskin middleware
     */
    public function setUp()
    {
        parent::setUp();

        // We will always use this route in our tests
        Router::get('/_tests/{skin}/test', function () {
            return view('version');
        })->middleware('setskin');
    }

    /**
     * This tests to determine if our base skin is functioning properly
     */
    public function testBaseSkin()
    {
        $response = $this->call('GET', '/_tests/base/test');
        $response->assertStatus(200);
        $response->assertSeeText(app()->version());
    }

    /**
     * When specifying a skin that is not found, the base skin should still function
     */
    public function testUnknownSkinStillFunctional()
    {
        $response = $this->call('GET', '/_tests/unknown/test');
        $response->assertStatus(200);
        $response->assertSeeText(app()->version());
    }

    /**
     * This tests to ensure the test skin can function
     */
    public function testCustomSkinFunctional()
    {
        // Use the test skin
        $response = $this->call('GET', '/_tests/test/test');
        $response->assertStatus(200);
        $response->assertSeeText('overridden version template');
    }

    /**
     * This tests to determine that if a view is returned that is NOT in the custom skin, but is
     * found in the base skin, the base skin blade template is rendered.
     */
    public function testCustomSkinOverrideMissingUseBaseSkin()
    {
        Router::get('/_tests/{skin}/testproduct', function () {
            return view('product');
        })->middleware('setskin');

        $response = $this->call('GET', '/_tests/test/testproduct');
        $response->assertStatus(200);
        $response->assertSeeText(config('app.name'));
    }
}
