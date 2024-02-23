<?php

namespace ProcessMaker\Listeners;

use CodeGreenCreative\SamlIdp\Jobs\SamlSso;
use Illuminate\Auth\Events\Authenticated;

class SamlAuthenticated
{
    /**
     * Listen for the Authenticated event
     *
     * @param  Authenticated $event [description]
     * @return [type]               [description]
     */
    public function handle(Authenticated $event)
    {
        if (
            in_array($event->guard, config('samlidp.guards')) &&
            request()->filled('SAMLRequest') &&
            !request()->is('saml/logout') &&
            request()->isMethod('get') &&
            $event->user->force_change_password === 0
        ) {
            abort(response(SamlSso::dispatchSync($event->guard), 302));
        }
    }
}
