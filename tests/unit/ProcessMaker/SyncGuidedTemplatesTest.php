<?php

namespace Tests\unit\ProcessMaker;

use Illuminate\Support\Facades\Http;
use ProcessMaker\Jobs\SyncGuidedTemplates;
use ProcessMaker\Models\ProcessCategory;
use Tests\TestCase;

class SyncGuidedTemplatesTest extends TestCase
{
    public function testHandle()
    {
        // Mock the HTTP response.
        $mockedResponse = file_get_contents(base_path('tests/Fixtures/guided_templates_response.json'));
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(json_decode($mockedResponse, true)),
        ]);

        ProcessCategory::factory()->create([
            'name' => 'Guided Templates',
            'status' => 'ACTIVE',
            'is_system' => 1,
        ]);

        // Create an instance of the job.
        $job = new SyncGuidedTemplates();

        // Call the handle method.
        $job->handle();

        // Assert that the necessary methods were called.
        Http::assertSent(function ($request) {
            return $request->url() === 'https://raw.githubusercontent.com/processmaker/wizard-templates/2023-winter/index.json';
        });

        // Assert that the process were created.
    }
}
