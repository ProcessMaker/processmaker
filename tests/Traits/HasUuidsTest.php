<?php
namespace Tests\Traits;

use Tests\TestCase;
use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;

class HasUuidsTest extends TestCase
{
    use RequestHelper;

    public function testSetUuidWhenCreating()
    {
        $screen = factory(Screen::class)->create();
        $this->assertNotNull($screen->uuid);
    }
}