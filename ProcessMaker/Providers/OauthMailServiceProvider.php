<?php

namespace ProcessMaker\Providers;

use Illuminate\Mail\MailServiceProvider;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Managers\OauthTransportManager;
use SmtpTransport;

class OauthMailServiceProvider extends MailServiceProvider
{
    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        // Make sure I dont need to register the parent parent::registerSwiftTransport();
        $this->app->singleton('swift.transport', function ($app) {
            return new OauthTransportManager($app->config);
        });
    }
}
