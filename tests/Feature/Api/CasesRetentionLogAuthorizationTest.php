<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Models\CaseRetentionPolicyLog;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\CaseRetentionLogExportNotification;
use Tests\TestCase;

/**
 * Authorization spec for retention deletion logs.
 *
 * These tests fail while any signed-in user can read or export the logs,
 * and pass once the page, the log query, and the export require an
 * administrator or a dedicated retention permission.
 */
class CasesRetentionLogAuthorizationTest extends TestCase
{
    public $withPermissions = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);

        // The admin page renders Laravel Mix asset URLs. The authorization
        // decision happens before those URLs are resolved.
        $this->app->singleton(Mix::class, function () {
            return new class {
                public function __invoke($path, $manifestDirectory = '')
                {
                    return '/' . ltrim((string) $path, '/');
                }
            };
        });
    }

    public function testSignedInUserWithoutAdminOrSettingsPermissionCannotListOrPageRetentionLogs(): void
    {
        $user = $this->userWithoutRetentionAccess();
        $older = $this->retentionLog([867530901, 867530902], 424242, now()->subDay());
        $newer = $this->retentionLog([867530903], 424243, now());

        $firstPage = $this->actingAs($user, 'api')->getJson(
            '/api/1.0/cases-retention/logs?page=1&per_page=1'
        );
        $secondPage = $this->actingAs($user, 'api')->getJson(
            '/api/1.0/cases-retention/logs?page=2&per_page=1'
        );
        $filtered = $this->actingAs($user, 'api')->getJson(
            '/api/1.0/cases-retention/logs?filter=' . $older->process_id . '&per_page=10&page=1'
        );

        $this->assertForbiddenWithoutLog($firstPage);
        $this->assertForbiddenWithoutLog($secondPage);
        $this->assertForbiddenWithoutLog($filtered);

        foreach ([$firstPage, $secondPage, $filtered] as $response) {
            $body = $response->getContent();
            $this->assertStringNotContainsString('867530901', $body);
            $this->assertStringNotContainsString('867530902', $body);
            $this->assertStringNotContainsString('867530903', $body);
            $this->assertStringNotContainsString((string) $older->process_id, $body);
            $this->assertStringNotContainsString((string) $newer->process_id, $body);
        }
    }

    public function testSignedInUserWithoutAdminOrSettingsPermissionCannotExportRetentionLogs(): void
    {
        Storage::fake('local');
        Notification::fake();

        $user = $this->userWithoutRetentionAccess();
        $log = $this->retentionLog([867530911, 867530912], 515151, now());

        $unfiltered = $this->actingAs($user, 'api')->getJson('/api/1.0/cases-retention/logs/export');
        $filtered = $this->actingAs($user, 'api')->getJson(
            '/api/1.0/cases-retention/logs/export?filter=' . $log->process_id
        );

        $files = Storage::disk('local')->allFiles('exports/case-retention');
        $contents = [];
        foreach ($files as $file) {
            $contents[$file] = Storage::disk('local')->get($file);
        }

        $this->assertSame(
            [],
            $files,
            'Retention export wrote a CSV before refusing the request: ' . json_encode($contents)
        );
        Notification::assertNothingSent();
        $this->assertForbiddenWithoutLog($unfiltered);
        $this->assertForbiddenWithoutLog($filtered);
        $this->assertStringNotContainsString('867530911', $unfiltered->getContent());
        $this->assertStringNotContainsString('867530912', $filtered->getContent());
    }

    public function testSignedInUserWithoutAdminOrSettingsPermissionCannotOpenRetentionPage(): void
    {
        $user = $this->userWithoutRetentionAccess();

        $response = $this->actingAs($user, 'web')->get('/admin/cases-retention');

        $this->assertForbiddenWithoutLog($response);
    }

    public function testAdministratorCanOpenRetentionPage(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->get('/admin/cases-retention');

        $response->assertOk();
        $response->assertViewIs('admin.cases-retention.index');
        $response->assertSee('Cases Retention Logs', false);
        $response->assertSee('download-retention-logs', false);
    }

    public function testAdministratorCanListAndPageRetentionLogs(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
        ]);
        $older = $this->retentionLog([867530921], 616161, now()->subDay());
        $newer = $this->retentionLog([867530922], 616162, now());

        $firstPage = $this->actingAs($admin, 'api')->getJson(
            '/api/1.0/cases-retention/logs?page=1&per_page=1'
        );
        $secondPage = $this->actingAs($admin, 'api')->getJson(
            '/api/1.0/cases-retention/logs?page=2&per_page=1'
        );
        $filtered = $this->actingAs($admin, 'api')->getJson(
            '/api/1.0/cases-retention/logs?filter=' . $older->process_id
        );

        $firstPage->assertOk();
        $firstPage->assertJsonFragment([
            'process_id' => $newer->process_id,
            'case_ids' => [867530922],
        ]);
        $secondPage->assertOk();
        $secondPage->assertJsonFragment([
            'process_id' => $older->process_id,
            'case_ids' => [867530921],
        ]);
        $filtered->assertOk();
        $filtered->assertJsonFragment([
            'process_id' => $older->process_id,
            'case_ids' => [867530921],
        ]);
        $this->assertStringNotContainsString('867530922', $filtered->getContent());
    }

    public function testAdministratorCanExportRetentionLogsContainingDeletedCaseIds(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->create([
            'is_administrator' => true,
        ]);
        $log = $this->retentionLog([867530931, 867530932], 717171, now());

        $response = $this->actingAs($admin, 'api')->getJson(
            '/api/1.0/cases-retention/logs/export?filter=' . $log->process_id
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $files = Storage::disk('local')->allFiles('exports/case-retention');
        $this->assertCount(1, $files);
        $csv = Storage::disk('local')->get($files[0]);
        $this->assertStringContainsString('867530931', $csv);
        $this->assertStringContainsString('867530932', $csv);
        $this->assertStringContainsString((string) $log->process_id, $csv);

        Notification::assertSentTo(
            $admin,
            CaseRetentionLogExportNotification::class,
            function (CaseRetentionLogExportNotification $notification) {
                return $notification->broadcastWith()['url'] !== null
                    && str_contains((string) $notification->broadcastWith()['url'], 'cases-retention/logs/export/download/');
            }
        );
    }

    private function userWithoutRetentionAccess(): User
    {
        $user = User::factory()->create([
            'is_administrator' => false,
            'status' => 'ACTIVE',
        ]);

        $this->assertFalse((bool) $user->is_administrator);
        $this->assertFalse($user->hasPermission('view-settings'));
        $this->assertFalse($user->can('view-settings'));

        return $user;
    }

    /**
     * @param  array<int, int>  $caseIds
     */
    private function retentionLog(array $caseIds, int $processId, $createdAt): CaseRetentionPolicyLog
    {
        $log = new CaseRetentionPolicyLog([
            'process_id' => $processId,
            'case_ids' => $caseIds,
            'deleted_count' => count($caseIds),
            'total_time_taken' => 4,
            'deleted_at' => $createdAt,
        ]);
        // created_at is guarded, so assign it directly. Equal timestamps make page order unstable.
        $log->created_at = $createdAt;
        $log->save();

        return $log->refresh();
    }

    private function assertForbiddenWithoutLog($response): void
    {
        $this->assertSame(403, $response->getStatusCode(), $response->getContent());
    }
}
