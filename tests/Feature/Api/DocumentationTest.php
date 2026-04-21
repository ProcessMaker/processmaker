<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\WithConsoleEvents;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use WithConsoleEvents;

    public function testGenerateSwaggerDocument()
    {
        \Artisan::call('l5-swagger:generate');
        $this->assertJson(
            file_get_contents(base_path('storage/api-docs/api-docs.json'))
        );
    }
}
