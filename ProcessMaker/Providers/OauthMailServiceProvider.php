<?php

namespace ProcessMaker\Providers;

use Illuminate\Mail\MailServiceProvider;
use ProcessMaker\Managers\OauthMailManager;

class OauthMailServiceProvider extends MailServiceProvider
{
    /**
     * Register the Illuminate Mailer instance.
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->bind('mail.manager', function ($app) {
            return new OauthMailManager($app);
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }
}
