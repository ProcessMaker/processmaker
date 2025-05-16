<?php

namespace Tests\Jobs;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\SyncDefaultTemplates;
use Tests\TestCase;

class SyncDefaultTemplatesTest extends TestCase
{
    public function test_job_skips_failed_template_fetches_and_logs_warning()
    {
        // Mock GitHub config.
        Config::set('services.github', [
            'base_url' => 'https://fake.test/',
            'template_repo' => 'repo',
            'template_branch' => 'main',
            'template_categories' => 'all',
        ]);

        // Fake HTTP responses.
        Http::fake([
            'https://fake.test/repo/main/index.json' => Http::response([
                'default' => [
                    [
                        'uuid' => 'template-uuid',
                        'name' => 'Broken Template',
                        'relative_path' => './broken-template.json',
                    ],
                ],
            ]),
            'https://fake.test/repo/main/broken-template.json' => Http::response(null, 500),
        ]);

        // Fake logging.
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Skipped template due to failed fetch');
            });

        // Run the job.
        $job = new SyncDefaultTemplates();
        $job->handle();
    }
}
