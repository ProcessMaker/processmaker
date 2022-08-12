<?php

namespace ProcessMaker\Providers;

use SmtpTransport;
use Illuminate\Mail\TransportManager;
use Illuminate\Mail\MailServiceProvider;
use ProcessMaker\Managers\OauthTransportManager;


class OauthMailServiceProvider extends MailServiceProvider
{
    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new OauthTransportManager($app->config);
        });
    }
}