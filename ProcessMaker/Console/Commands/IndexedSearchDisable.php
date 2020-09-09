<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\Setting;
use ProcessMaker\Traits\SupportsNonInteraction;

class IndexedSearchDisable extends Command
{
    use SupportsNonInteraction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indexed-search:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable indexed search';

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
        if ($this->interactive()) {
            $confirmed = $this->confirm("Are you sure you wish to disable indexed search?");
        } else {
            $confirmed = true;
        }

        if ($confirmed) {
            $setting = Setting::updateOrCreate(
                [ 'key' => 'indexed-search' ],
                [ 'config' => [ 'enabled' => false ], ]
            );

            if ($setting->config['enabled'] === false) {
                $this->line('Indexed search has been disabled.');
            }
        }
    }
}
