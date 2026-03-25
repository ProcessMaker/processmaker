<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ProcessMaker\Jobs\DownloadCaseRetentionLogExport;
use ProcessMaker\Models\User;
use Tests\TestCase;

class CasesRetentionLogsExportTest extends TestCase
{
    use RefreshDatabase;

    public function testExportEndpointDispatchesJob(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'is_administrator' => true,
        ]);

        $response = $this->actingAs($user, 'api')
            ->get('/api/1.0/cases-retention/logs/export');

        $response->assertOk();
        $response->assertJson(['success' => true]);
        Bus::assertDispatched(DownloadCaseRetentionLogExport::class);
    }

    public function testExportPassesFilterToJob(): void
    {
        Bus::fake();

        $user = User::factory()->create([
            'is_administrator' => true,
        ]);

        $this->actingAs($user, 'api')
            ->get('/api/1.0/cases-retention/logs/export?filter=99');

        Bus::assertDispatched(DownloadCaseRetentionLogExport::class, function (DownloadCaseRetentionLogExport $job) {
            return $job->getFilter() === '99';
        });
    }

    public function testSignedDownloadStreamsCsvFile(): void
    {
        Storage::fake('local');
        $token = (string) Str::uuid();
        $path = 'exports/case-retention/' . $token . '.csv';
        Storage::disk('local')->makeDirectory('exports/case-retention');
        Storage::disk('local')->put($path, "\xEF\xBB\xBF1,2,3");

        $url = URL::temporarySignedRoute(
            'api.cases-retention.logs.export.download',
            now()->addMinutes(10),
            ['token' => $token],
        );

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
