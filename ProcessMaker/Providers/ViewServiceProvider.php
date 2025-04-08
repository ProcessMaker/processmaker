<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer(['layouts.layoutnext', 'layouts.layout'], function ($view) {
            $messages = config('notifications.messages');
            $view->with('notificationMessages', json_encode($messages));
        });
    }
}
