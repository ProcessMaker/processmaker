<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Events\QueueManagementAccessed;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Providers\TenantQueueServiceProvider;

class QueuesController extends Controller
{
    public function index()
    {
        if (!auth()->user()->is_administrator) {
            throw new AuthorizationException();
        }

        if (config('app.multitenancy')) {
            if (!TenantQueueServiceProvider::allowAllTenats()) {
                // Its multitenancy and they don't have access to all tenants so
                // redirect to the tenant-filtered queue management page.
                // Otherwise, show the horizon queue manager.
                return redirect()->route('tenant-queue.index');
            }
        }

        // Register the Event
        QueueManagementAccessed::dispatch();

        return view('admin.queues.index');
    }
}
