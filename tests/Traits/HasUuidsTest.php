<?php

namespace Tests\Traits;

use ProcessMaker\Models\Screen;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class HasUuidsTest extends TestCase
{
    use RequestHelper;

    public function testSetUuidWhenCreating()
    {
        $screen = factory(Screen::class)->create();
        $this->assertNotNull($screen->uuid);
    }
}
