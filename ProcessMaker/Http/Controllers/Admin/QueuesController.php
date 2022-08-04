<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Auth\Access\AuthorizationException;
use ProcessMaker\Http\Controllers\Controller;

class QueuesController extends Controller
{
    public function index()
    {
        if (auth()->user()->is_administrator) {
            return view('admin.queues.index');
        }

        throw new AuthorizationException();
    }
}
