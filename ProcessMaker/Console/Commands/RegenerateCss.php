<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CompileSass;
use ProcessMaker\Jobs\CompileUI;
use ProcessMaker\Models\Setting;
use ProcessMaker\PackageChecker;

class RegenerateCss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:regenerate-css';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("\nStarting CSS compiling...");
        (new CompileUI())->handle();
        $this->info("\nCSS files have been generated.");
    }
}
