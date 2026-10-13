<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Jobs\TestStatusJob;
use Tests\TestCase;

class HorizonConfigTest extends TestCase
{
    private const ENVIRONMENTS = ['production', 'local', 'staging'];

    private const SUPERVISORS = ['supervisor-bpmn', 'supervisor-1'];

    protected function tearDown(): void
    {
        $this->unsetMaxJobsEnvironmentVariables();

        parent::tearDown();
    }

    public function testMaxJobsUsesRecommendedDefaultsForAllSupervisors(): void
    {
        $this->unsetMaxJobsEnvironmentVariables();

        $configuration = include base_path('config/horizon.php'); // NOSONAR: load fresh config after changing environment variables.

        foreach (self::ENVIRONMENTS as $environment) {
            $this->assertSame(10, $configuration['environments'][$environment]['supervisor-bpmn']['maxJobs']);
            $this->assertSame(100, $configuration['environments'][$environment]['supervisor-1']['maxJobs']);
        }
    }

    public function testMaxJobsCanBeDisabledWithZero(): void
    {
        putenv('PM4_HORIZON_SUPERVISOR_BPMN_MAX_JOBS=0');
        putenv('PM4_HORIZON_SUPERVISOR_1_MAX_JOBS=0');

        $configuration = include base_path('config/horizon.php'); // NOSONAR: load fresh config after changing environment variables.

        foreach (self::ENVIRONMENTS as $environment) {
            foreach (self::SUPERVISORS as $supervisor) {
                $this->assertSame(
                    0,
                    $configuration['environments'][$environment][$supervisor]['maxJobs'],
                    "{$environment}.{$supervisor} should allow maxJobs to be disabled"
                );
            }
        }
    }

    public function testMaxJobsCanBeConfiguredAsTwoForBpmnAndDefaultSupervisors(): void
    {
        putenv('PM4_HORIZON_SUPERVISOR_BPMN_MAX_JOBS=2');
        putenv('PM4_HORIZON_SUPERVISOR_1_MAX_JOBS=2');

        $configuration = include base_path('config/horizon.php'); // NOSONAR: load fresh config after changing environment variables.

        foreach (self::ENVIRONMENTS as $environment) {
            foreach (self::SUPERVISORS as $supervisor) {
                $this->assertSame(
                    2,
                    $configuration['environments'][$environment][$supervisor]['maxJobs'],
                    "{$environment}.{$supervisor} should use the configured maxJobs value"
                );
            }
        }
    }

    public function testFiveJobsContinueProcessingAcrossWorkersWithMaxJobsTwo(): void
    {
        $queue = 'maxjobs-test-' . bin2hex(random_bytes(4));
        $jobPrefix = 'maxjobs-test-' . bin2hex(random_bytes(4));
        $maxJobs = 2;

        Config::set('queue.default', 'redis');
        Config::set('horizon.environments.local.supervisor-bpmn.maxJobs', $maxJobs);

        foreach (range(1, 5) as $number) {
            TestStatusJob::dispatch(
                "{$jobPrefix}-{$number}",
                'Horizon maxJobs integration test'
            )->onQueue($queue);
        }

        foreach ([2, 4, 5] as $expectedCount) {
            $exitCode = Artisan::call('queue:work', [
                'connection' => 'redis',
                '--queue' => $queue,
                '--max-jobs' => $maxJobs,
                '--stop-when-empty' => true,
                '--tries' => 1,
                '--memory' => 1024,
            ]);

            $this->assertSame(0, $exitCode);
            $this->assertSame(
                $expectedCount,
                DB::table('test_status')
                    ->where('name', 'like', "{$jobPrefix}-%")
                    ->count()
            );
        }
    }

    private function unsetMaxJobsEnvironmentVariables(): void
    {
        putenv('PM4_HORIZON_SUPERVISOR_BPMN_MAX_JOBS');
        putenv('PM4_HORIZON_SUPERVISOR_1_MAX_JOBS');
    }
}
