<?php
namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;

class DocumentationTest extends TestCase
{
    public function testGenerateSwaggerDocument()
    {
        \Artisan::call('l5-swagger:generate');
        $this->assertJson(
            file_get_contents(base_path('storage/api-docs/api-docs.json'))
        );
    }
}