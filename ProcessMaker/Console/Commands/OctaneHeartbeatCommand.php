<?php

declare(strict_types=1);

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Health\OctaneHeartbeat;
use ProcessMaker\Health\OctaneHeartbeatOutput;

class OctaneHeartbeatCommand extends Command
{
    protected $signature = 'octane:heartbeat
                            {--ready : Verify database (and Redis when required) before reporting healthy}
                            {--php-ini : Show CLI PHP vs FrankenPHP worker php.ini comparison}';

    protected $description = 'Fast Octane liveness/readiness probe and worker php.ini inspection';

    public function handle(OctaneHeartbeat $heartbeat): int
    {
        $showPhpIni = (bool) $this->option('php-ini');
        $result = $heartbeat->probe((bool) $this->option('ready'), $showPhpIni);

        (new OctaneHeartbeatOutput($this))->render($result, $showPhpIni);

        return $result->exitCode($showPhpIni);
    }
}
