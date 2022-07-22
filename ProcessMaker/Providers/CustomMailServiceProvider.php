<?php

namespace ProcessMaker\Providers;

use App\Helpers\Drivers\OutlookMailDriver;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Mail\TransportManager;
use ProcessMaker\Package\Office365Driver;
use ProcessMaker\Package\GmailDriver;

class CustomMailServiceProvider extends MailServiceProvider
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
            $driver = app()->config['mail.driver'];
            switch ($driver) {
                case 'gmail':
                    $callback = new GmailDriver();
                    $transport->extend($driver, $callback($transport));
                    return $transport;
                    break;
                case 'office365': 
                    $callback = new Office365Driver();
                    $transport->extend($driver, $callback($transport));
                    return $transport;
                    break;
                default:
                    break;
            }
        });
    }
}
