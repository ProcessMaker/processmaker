<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Models\User;
use ProcessMaker\Providers\AuthServiceProvider;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

/**
 * Security-log index sorting must not send client order_by text to the database as SQL.
 *
 * These tests fail while ProcessMaker\Http\Controllers\Api\SecurityLogController::index
 * copies order_by into DB::raw. They pass when order_by is limited to a fixed list of
 * security-log columns and that name is passed to the query builder without DB::raw.
 *
 * Reviewed and not vulnerable to this bug: CasesRetentionController::logs checks
 * order_by against LOG_SORT_COLUMNS before the same preg_replace and DB::raw call.
 * Other orderBy($request) call sites pass a plain string, which the query grammar
 * quotes, and are outside this bug.
 */
class SecurityLogOrderByInjectionTest extends TestCase
{
    use RequestHelper;

    private const MARKER_USERNAME = 'orderinjectionmarker';

    private const LOCK_NAME = 'pm_security_log_order_probe';

    private const DOTTED_LOCK_NAME = 'pm_security_log_order_probe_dotted';

    private const STACK_LOCK_NAME = 'pm_security_log_order_probe_stack';

    private array $attemptedSql = [];

    private bool $sqlCaptureRegistered = false;

    protected function withUserSetup()
    {
        $this->user->is_administrator = false;
        $this->user->save();

        $permission = Permission::byName('view-security-logs');
        $this->user->permissions()->attach($permission->id);
        $this->user->refresh();
        $this->user->invalidatePermissionCache();

        $asp = new AuthServiceProvider(app());
        $asp->boot();
        $asp->defineGates();
    }

    protected function tearDown(): void
    {
        foreach ([self::LOCK_NAME, self::DOTTED_LOCK_NAME, self::STACK_LOCK_NAME] as $lockName) {
            DB::select('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }

        parent::tearDown();
    }

    public function test_known_security_log_columns_still_sort(): void
    {
        $this->seedSortableLogs();

        $byEvent = $this->apiCall('GET', '/security-logs', [
            'order_by' => 'event',
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);
        $byEvent->assertStatus(200);
        $this->assertSame(['alpha', 'beta'], $this->eventOrder($byEvent));

        $byEventDesc = $this->apiCall('GET', '/security-logs', [
            'order_by' => 'event',
            'order_direction' => 'desc',
            'per_page' => 10,
        ]);
        $byEventDesc->assertStatus(200);
        $this->assertSame(['beta', 'alpha'], $this->eventOrder($byEventDesc));

        $byBrowser = $this->apiCall('GET', '/security-logs', [
            'order_by' => 'meta.browser.name',
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);
        $byBrowser->assertStatus(200);
        $this->assertSame(['alpha', 'beta'], $this->eventOrder($byBrowser));

        $byOs = $this->apiCall('GET', '/security-logs', [
            'order_by' => 'meta.os.name',
            'order_direction' => 'desc',
            'per_page' => 10,
        ]);
        $byOs->assertStatus(200);
        $this->assertSame(['alpha', 'beta'], $this->eventOrder($byOs));
    }

    public function test_order_by_without_a_dot_cannot_read_another_table(): void
    {
        $this->seedSortableLogs();
        $marker = User::factory()->create([
            'username' => self::MARKER_USERNAME,
            'is_administrator' => false,
        ]);
        $this->assertNotNull($marker->password);

        $this->startSqlCapture();

        $byteIsPresent = $this->apiCall('GET', '/security-logs', [
            'order_by' => $this->passwordByteSort('> 0'),
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);
        $byteIsAboveAnyByte = $this->apiCall('GET', '/security-logs', [
            'order_by' => $this->passwordByteSort('> 255'),
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);

        $this->assertSame(
            $this->eventOrder($byteIsAboveAnyByte),
            $this->eventOrder($byteIsPresent),
            'order_by changed security-log order using password bytes from users.'
        );
        $this->assertExpressionDidNotRun(self::LOCK_NAME);
        $byteIsPresent->assertStatus(422);
        $byteIsAboveAnyByte->assertStatus(422);
    }

    public function test_order_by_with_a_leading_expression_and_a_dot_is_not_executed(): void
    {
        $this->seedSortableLogs();
        User::factory()->create([
            'username' => self::MARKER_USERNAME,
            'is_administrator' => false,
        ]);

        $this->startSqlCapture();

        $response = $this->apiCall('GET', '/security-logs', [
            'order_by' => "(SELECT GET_LOCK('" . self::DOTTED_LOCK_NAME . "', 0) FROM users WHERE username = '" . self::MARKER_USERNAME . "').marker",
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);

        $this->assertExpressionDidNotRun(self::DOTTED_LOCK_NAME);
        $response->assertStatus(422);
    }

    public function test_order_by_comma_expression_is_not_executed(): void
    {
        $this->seedSortableLogs();

        $this->startSqlCapture();

        $response = $this->apiCall('GET', '/security-logs', [
            'order_by' => "id,(SELECT GET_LOCK('" . self::STACK_LOCK_NAME . "', 0))",
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);

        $this->assertExpressionDidNotRun(self::STACK_LOCK_NAME);
        $response->assertStatus(422);
    }

    public function test_order_by_expression_is_not_run_without_view_security_logs(): void
    {
        $this->user->permissions()->detach();
        $this->user->invalidatePermissionCache();
        $this->seedSortableLogs();

        $this->startSqlCapture();

        $response = $this->apiCall('GET', '/security-logs', [
            'order_by' => $this->passwordByteSort('> 0'),
            'order_direction' => 'asc',
            'per_page' => 10,
        ]);

        $response->assertStatus(403);
        $this->assertExpressionDidNotRun(self::LOCK_NAME);
    }

    private function seedSortableLogs(): void
    {
        SecurityLog::factory()->create([
            'event' => 'beta',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => ['name' => 'Linux'],
                'browser' => ['name' => 'Firefox'],
            ],
        ]);
        SecurityLog::factory()->create([
            'event' => 'alpha',
            'user_id' => $this->user->id,
            'meta' => [
                'os' => ['name' => 'Windows'],
                'browser' => ['name' => 'Chrome'],
            ],
        ]);
    }

    /**
     * Sort by event when the first byte of users.password for the marker is in range,
     * and by id otherwise. The two comparisons disagree for every stored password byte,
     * so a raw ORDER BY returns different security-log orders. GET_LOCK runs only when
     * that users row is read, including when the response is discarded after the query.
     */
    private function passwordByteSort(string $comparison): string
    {
        return '(CASE WHEN (SELECT GET_LOCK(\'' . self::LOCK_NAME . '\', 0) FROM users WHERE username = \'' . self::MARKER_USERNAME . '\') = 1'
            . ' AND (SELECT ASCII(SUBSTRING(password, 1, 1)) FROM users WHERE username = \'' . self::MARKER_USERNAME . '\') ' . $comparison
            . ' THEN event ELSE id END)';
    }

    private function eventOrder($response): array
    {
        $data = $response->json('data');
        if (!is_array($data)) {
            return [];
        }

        return array_column($data, 'event');
    }

    private function startSqlCapture(): void
    {
        $this->attemptedSql = [];
        if (!$this->sqlCaptureRegistered) {
            DB::beforeExecuting(function (string $query) {
                $this->attemptedSql[] = $query;
            });
            $this->sqlCaptureRegistered = true;
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    private function assertExpressionDidNotRun(string $lockName): void
    {
        $held = DB::selectOne('SELECT IS_USED_LOCK(?) AS held', [$lockName]);
        $this->assertNull($held->held, 'order_by was executed before the request was refused.');

        $sql = implode("\n", array_merge(
            $this->attemptedSql,
            array_column(DB::getQueryLog(), 'query')
        ));
        $this->assertStringNotContainsString($lockName, $sql);
        $this->assertStringNotContainsString(self::MARKER_USERNAME, $sql);
        $this->assertStringNotContainsString('GET_LOCK', $sql);
    }
}
