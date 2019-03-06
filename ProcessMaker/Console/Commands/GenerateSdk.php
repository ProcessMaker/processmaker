<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\BuildSdk;
use \Exception;

class GenerateSdk extends Command
{
    /**
     * The name and signature of the console command.
     *
     *
     * @var string
     */
    protected $signature = 'bpm:sdk {language}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates sdk for different languages';

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
        try {
            $builder = new BuildSdk(base_path(), true);
            $builder->setLang($this->argument('language'));
            $builder->run();
        } catch(Exception $e) {
            echo "ERROR: {$e->getMessage()}\n";
            exit(1);
        }
    }
}
