<?php

namespace Tests;

use Illuminate\Foundation\Console\ConfigCacheCommand;
use ProcessMaker\Models\Setting;

class ConfigCacheCommandMock extends ConfigCacheCommand
{
    public function handle()
    {
        foreach (Setting::select('id', 'key', 'config', 'format')->get() as $setting) {
            config([$setting->key => $setting->config]);
        }
    }
}
