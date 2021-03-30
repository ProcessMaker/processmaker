<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use ProcessMaker\Jobs\TestStatusJob;

class TestStatusController extends Controller
{
    public function test()
    {
        TestStatusJob::dispatch('BroadcastService', 'Echo message received')->delay(Carbon::now()->addSeconds(5));
        return view('test.status');
    }
}
