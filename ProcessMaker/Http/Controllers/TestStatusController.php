<?php

namespace ProcessMaker\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Jobs\TestStatusJob;

class TestStatusController extends Controller
{
    public function test()
    {
        TestStatusJob::dispatch('BroadcastService', 'Echo message received')->delay(Carbon::now()->addSeconds(5));
        return view('test.status');
    }

    public function testAcknowledgement()
    {
        DB::table('test_status')->insert([
            'name' => 'Message acknowledgement',
            'description' => 'Client received the message and send back an acknowledgement',
        ]);
    }

    public function email()
    {
        DB::table('test_status')->insert([
            'name' => 'Email received',
            'description' => 'Email received',
        ]);
        return 'Email received.<script>setTimeout("window.close()", 2000);</script>';
    }
}
