<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Events\QueueManagementAccessed;
use ProcessMaker\Http\Controllers\Controller;

class QueuesController extends Controller
{
    public function index()
    {
        if (auth()->user()->is_administrator) {
            // Register the Event
            QueueManagementAccessed::dispatch();

            return view('admin.queues.index');
        }

        throw new AuthorizationException();
    }
}
