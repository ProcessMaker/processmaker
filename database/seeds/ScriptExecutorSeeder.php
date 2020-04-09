<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorSeeder extends Seeder
{
    public function run()
    {
        foreach (config('script-runners') as $key => $config) {
            if ($key === 'javascript') {
                $key = 'node';
            }
            try {
                if (isset($this->command)) {
                    $this->command->line("Running docker-executor-{$key}:install");
                }
                \Artisan::call("docker-executor-{$key}:install");
            } catch(\Predis\Connection\ConnectionException $e) {
                // horizon:terminate command when redis is not configured
                // this happens in CircleCI
            }
        }
    }
}