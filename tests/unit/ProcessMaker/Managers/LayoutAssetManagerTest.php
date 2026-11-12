<?php

namespace Tests\Unit\ProcessMaker\Managers;

use Illuminate\Http\Request;
use InvalidArgumentException;
use ProcessMaker\Managers\LayoutAssetManager;
use Tests\TestCase;

class LayoutAssetManagerTest extends TestCase
{
    private LayoutAssetManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new LayoutAssetManager();
    }

    public function testInboxRouteUsesInboxProfile(): void
    {
        $assets = $this->manager->forRequest(Request::create('/inbox'));

        $this->assertSame('inbox', $assets['profile']);
        $this->assertSame('js/app-core.js', $assets['app']);
        $this->assertSame('js/app-layout-core.js', $assets['app_layout']);
        $this->assertFalse($assets['modeler_vendor']);
        $this->assertFalse($assets['monaco']);
    }

    public function testTasksRouteUsesInboxProfile(): void
    {
        $assets = $this->manager->forRequest(Request::create('/tasks'));

        $this->assertSame('inbox', $assets['profile']);
        $this->assertSame('js/app-core.js', $assets['app']);
    }

    public function testInboxSubRouteUsesInboxProfile(): void
    {
        $assets = $this->manager->forRequest(Request::create('/inbox/process/1'));

        $this->assertSame('inbox', $assets['profile']);
    }

    public function testDefaultRouteUsesFullProfile(): void
    {
        $assets = $this->manager->forRequest(Request::create('/processes'));

        $this->assertSame('default', $assets['profile']);
        $this->assertSame('js/app.js', $assets['app']);
        $this->assertSame('js/app-layout.js', $assets['app_layout']);
        $this->assertTrue($assets['modeler_vendor']);
        $this->assertTrue($assets['monaco']);
    }

    public function testRequiresThrowsForUnknownAssetFlag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown layout asset flag: missing');

        $this->manager->requires('missing', Request::create('/inbox'));
    }

    public function testLayoutAssetsHelperReturnsResolvedProfile(): void
    {
        $assets = layoutAssets(Request::create('/tasks'));

        $this->assertSame('inbox', $assets['profile']);
        $this->assertFalse($assets['monaco']);
    }
}
