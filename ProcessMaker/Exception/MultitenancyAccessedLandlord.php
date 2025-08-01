<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MultitenancyAccessedLandlord extends Exception
{
    public function render(Request $request): Response
    {
        return response()->view('multitenancy.landlord-landing-page');
    }

    public function report()
    {
        // Don't report this exception.
    }
}
