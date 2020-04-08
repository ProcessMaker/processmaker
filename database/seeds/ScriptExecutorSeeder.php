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
                \Artisan::call("docker-executor-{$key}:install");
            } catch(\Predis\Connection\ConnectionException $e) {
                // horizon:terminate command when redis is not configured
                // this happens in CircleCI
            }
        }
    }
}