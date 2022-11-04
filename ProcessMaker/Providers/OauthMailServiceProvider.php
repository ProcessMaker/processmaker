<?php

namespace ProcessMaker\Providers;

use Illuminate\Mail\MailServiceProvider;
use ProcessMaker\Managers\OauthMailManager;

class OauthMailServiceProvider extends MailServiceProvider
{
    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->bind('mail.manager', function ($app) {
            return new OauthMailManager($app);
        });
    }
}
