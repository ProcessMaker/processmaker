<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MultitenancyNoTenantFound extends Exception
{
    public function render(Request $request): Response
    {
        return abort(499, 'No tenant found for host: ' . $request->getHost());
    }

    public function report()
    {
        // Don't report this exception.
    }
}
