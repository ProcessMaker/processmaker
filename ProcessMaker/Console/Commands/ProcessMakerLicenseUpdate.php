<?php

namespace ProcessMaker\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessMakerLicenseUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:license-update {licenseFile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the license from a given URL or local path and store it in local disk';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $input = $this->argument('licenseFile');

        try {
            $content = file_get_contents($input);
            if (!$this->isValidLicenseContent($content)) {
                return 1;
            }

            Storage::disk('local')->put('license.json', $content);

            $this->info('Calling package:discover to update the package cache with enabled packages');
            Artisan::call('package:discover');
            $this->info(Artisan::output());

            $this->info('License updated successfully');
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Validates the license content.
     *
     * @param string $content
     * @return bool
     */
    protected function isValidLicenseContent(string $content): bool
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('The provided license does not have a valid format.');

            return false;
        }

        if (!isset($data['expires_at']) || !is_string($data['expires_at'])) {
            $this->error('The provided license does not have a valid "expires_at" property.');

            return false;
        }

        try {
            Carbon::parse($data['expires_at']);
        } catch (Exception $e) {
            $this->error('The "expires_at" property is not a valid date.');

            return false;
        }

        if (!isset($data['packages']) || !is_array($data['packages'])) {
            $this->error('The provided license does not have a valid "packages" property.');

            return false;
        }

        return true;
    }
}
