<?php

namespace ProcessMaker\Jobs;

use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use ProcessMaker\Models\Setting;

class ImportSettings
{
    use Dispatchable;

    private $file;

    private $imported = [];

    private $settings = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file)
    {
        $this->file = json_decode($file);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->verifyFile();
        $this->parseFile();
        $this->parseSettings();
        return $this->returnResults();
    }

    private function verifyFile()
    {
        if (isset($this->file->type)) {
            if ($this->file->type == 'settings_package') {
                return true;
            }
        }

        throw new Exception('The file format is incorrect.');
    }

    private function parseFile()
    {
        if (isset($this->file->settings) && is_array($this->file->settings)) {
            $this->settings = $this->file->settings;
        }
    }

    private function parseSettings()
    {
        foreach ($this->settings as $setting) {
            if (property_exists($setting, 'key') && property_exists($setting, 'config')) {
                $db = Setting::byKey($setting->key);
                if ($db) {
                    if (! $db->readonly) {
                        $db->config = $setting->config;
                        $saved = $db->save();
                        if ($saved) {
                            $this->imported[] = $db->refresh();
                        }
                    }
                }
            }
        }
    }

    private function returnResults()
    {
        if (count($this->imported)) {
            return $this->imported = collect($this->imported);
        } else {
            throw new Exception('No matching settings were found.');
        }
    }
}
