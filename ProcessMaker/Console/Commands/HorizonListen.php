<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Queue\Console\ListenCommand as BaseCommand;

// https://github.com/laravel/horizon/issues/624
class HorizonListen extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'horizon:listen
        {connection? : The name of connection}
        {--name=default : The name of the worker}
        {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
        {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
        {--max-time=0 : The maximum number of seconds the worker should run}
        {--max-jobs=0 : The number of jobs to process before stopping}
        {--force : Force the worker to run even in maintenance mode}
        {--memory=128 : The memory limit in megabytes}
        {--queue= : The queue to listen on}
        {--sleep=3 : Number of seconds to sleep when no job is available}
        {--timeout=60 : The number of seconds a child process can run}
        {--tries=1 : Number of times to attempt a job before logging it failed}
        {--supervisor= : The name of the supervisor the worker belongs to}
        {--rest=0 : Number of seconds to rest between jobs}';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (config('horizon.fast_termination')) {
            ignore_user_abort(true);
        }

        parent::handle();
    }
}
