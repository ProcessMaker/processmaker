<?php

namespace ProcessMaker\Exception;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProcessMaker\Facades\Metrics;

class MultitenancyAccessedLandlord extends Exception
{
    public function render(Request $request): Response
    {
        // If we're trying to access the /metrics route, collect landlord metrics and render them
        if ($request->path() === 'metrics') {
            Metrics::collectQueueMetrics();

            return response(Metrics::renderMetrics(), 200, [
                'Content-Type' => 'text/plain; version=0.0.4',
            ]);
        }

        return response()->view('multitenancy.landlord-landing-page');
    }

    public function report()
    {
        // Don't report this exception.
    }
}
