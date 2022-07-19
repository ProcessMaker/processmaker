<?php

namespace ProcessMaker\Providers;

use App\Helpers\Drivers\OutlookMailDriver;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Package\GmailDriver;

class GmailProvider extends MailServiceProvider
{
    /**
     * Register the Swift Transport instance.
     *
     * @return void
     */
    protected function registerSwiftTransport()
    {
        parent::registerSwiftTransport();
        
        $this->app->extend('swift.transport', function (TransportManager $transport) {
            $callback = new GmailDriver();
            $transport->extend('gmail', $callback($transport));
            return $transport;
        });
    }
}
