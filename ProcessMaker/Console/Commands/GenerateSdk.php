<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use ProcessMaker\BuildSdk;

class GenerateSdk extends Command
{
    /**
     * The name and signature of the console command.
     *
     *
     * @var string
     */
    protected $signature = 'processmaker:sdk {language=none} {output=storage/api} {--list-options} {--clean} {--user-id=}';

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
        $jsonPath = base_path('storage/api-docs/api-docs.json');
        $builder = new BuildSdk($jsonPath, $this->argument('output'));

        $userId = $this->options()['user-id'];
        if ($userId) {
            $builder->setUserId($userId);
        }

        if ($this->argument('language') === 'none') {
            $this->info(
                "No language specified. Choose one of these: \n" .
                implode(', ', $builder->getAvailableLanguages())
            );

            return;
        }
        $builder->setLang($this->argument('language'));
        if ($this->options()['list-options']) {
            $this->info($builder->getOptions());

            return;
        }

        // Delete all files except .git folder
        if ($this->options()['clean']) {
            $folder = $this->argument('output');
            if (substr($folder, -1) !== '/') {
                $folder .= '/';
            }
            exec('rm -rf ' . $folder . '*');
        }

        $this->info($builder->run());
    }
}
