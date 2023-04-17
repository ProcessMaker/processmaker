<?php

namespace ProcessMaker\Listeners;

use Illuminate\Support\Facades\Artisan;

class UpdateDataLakeViews
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        $args = request()->server('argv');
        if (is_array($args) && in_array('--pretend', $args)) {
            return;
        }
        Artisan::call('processmaker:create-data-lake-views');
    }
}
