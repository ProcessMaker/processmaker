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
        $screen = Screen::factory()->create();
        $this->assertNotNull($screen->uuid);
    }
}
