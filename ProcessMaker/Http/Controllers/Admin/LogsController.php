<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class LogsController extends Controller
{
    /**
     * Display the logs index page.
     * This view loads log components from installed packages (package-email-start-event, package-ai).
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('admin.logs.index');
    }
}
