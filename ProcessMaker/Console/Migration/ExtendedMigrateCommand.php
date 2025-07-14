<?php

namespace ProcessMaker\Console\Migration;

use Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Cache\Screens\ScreenCacheFactory;
use ProcessMaker\Cache\Settings\SettingCacheFactory;
use ProcessMaker\Helpers\CachedSchema;
use ProcessMaker\Models\ProcessMakerModel;

class ExtendedMigrateCommand extends BaseMigrateCommand
{
    public function handle(): void
    {
        Cache::tags(ProcessMakerModel::MIGRATION_COLUMNS_CACHE_KEY)->flush();
        Cache::tags(CachedSchema::CACHE_TAG)->flush();
        SettingCacheFactory::getSettingsCache()->clear();
        ScreenCacheFactory::getScreenCache()->clearCompiledAssets();

        parent::handle();
    }
}
