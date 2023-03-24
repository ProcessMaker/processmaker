<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Artisan;

class UpdateDataLakeViews
{
    /**
     * Handle the event.
     *
     * @param MigrationsEnded $event
     * @return void
     */
    public function handle($event)
    {
        Artisan::call('processmaker:create-data-lake-views');
    }
}
