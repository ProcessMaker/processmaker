<?php

namespace Tests\Feature\ImportExport;

use ProcessMaker\ImportExport\Options;
use Tests\TestCase;

class OptionsTest extends TestCase
{
    public function testImportingUserIdIsOptionalImportContext()
    {
        $this->assertNull((new Options([]))->importingUserId);
        $this->assertSame(123, (new Options([], 123))->importingUserId);
    }
}
