<?php

namespace ProcessMaker\Console\Migration;

use Illuminate\Database\Console\Migrations\MigrateCommand as BaseMigrateCommand;
use Illuminate\Support\Facades\Cache;
use ProcessMaker\Models\ProcessMakerModel;

class ExtendedMigrateCommand extends BaseMigrateCommand
{

    public function handle(): void
    {
        Cache::tags(ProcessMakerModel::MIGRATION_COLUMNS_CACHE_KEY)->flush();

        parent::handle();
    }
}
