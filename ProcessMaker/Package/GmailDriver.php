<?php

namespace ProcessMaker\Package;

use Illuminate\Mail\TransportManager;
use Swift_SmtpTransport;
use ProcessMaker\Managers\GmailTransportManager;

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