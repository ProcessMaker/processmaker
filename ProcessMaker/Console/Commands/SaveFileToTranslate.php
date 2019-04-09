<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;

class SaveFileToTranslate extends Command
{
    /**
     * The name and signature of the console command.
     *
     *
     * @var string
     */
    protected $signature = 'bpm:save_file_to_translate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a json object with empty values to translate to other languages';

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
        $path = resource_path('lang/en.json');
        $lang = json_decode(file_get_contents($path), true);
        array_walk($lang, function(&$v) { $v = ""; });
        ksort($lang);
        file_put_contents('en.blank.json', json_encode($lang, JSON_PRETTY_PRINT));
    }
}
