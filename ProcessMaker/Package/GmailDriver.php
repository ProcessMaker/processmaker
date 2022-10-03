<?php

namespace ProcessMaker\Package;

use Illuminate\Mail\TransportManager;
use ProcessMaker\Managers\GmailTransportManager;
use Swift_SmtpTransport;

class GmailDriver
{
    public function __invoke(TransportManager $manager)
    {
        return function ($app) {
            $config = $app['config']->get('services.gmail', []);

            return new GmailTransportManager($config);
        };
    }
}
