<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ProcessMakerLicenseRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:license-remove {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the license.json file from the system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (Storage::disk('local')->exists('license.json')) {
            if ($this->option('force') || $this->confirm('Are you sure you want to remove the license.json file?')) {
                Storage::disk('local')->delete('license.json');
                $this->info('license.json removed successfully!');

                $this->info('Calling package:discover to update the package cache with enabled packages');
                Artisan::call('package:discover');
                $this->info(Artisan::output());
            } else {
                $this->info('Operation cancelled. license.json was not removed.');
            }
        } else {
            $this->error('license.json does not exist on the local disk.');
        }

        return 0;
    }
}
