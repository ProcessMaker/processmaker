<?php

namespace ProcessMaker\Providers;

use App\Helpers\Drivers\OutlookMailDriver;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Package\Office365Driver;

class Office365Provider extends MailServiceProvider
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
            $callback = new Office365Driver();
            $transport->extend('office365', $callback($transport));
            return $transport;
        });
    }
}
