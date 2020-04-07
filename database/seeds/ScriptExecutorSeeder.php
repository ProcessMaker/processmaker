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
            \Artisan::call("docker-executor-{$key}:install");
        }
    }
}