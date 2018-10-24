<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;

class RequestController extends Controller
{
    /**
     * Get the list of requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        //load counters
        $allRequest = ProcessRequest::count();
        $startedMe = ProcessRequest::startedMe(Auth::user()->uuid)->count();
        $inProgress = ProcessRequest::inProgress()->count();
        $completed = ProcessRequest::completed()->count();

        return view('requests.index', compact(['allRequest', 'startedMe', 'inProgress', 'completed']));
    }

    /**
     * Edit a request
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function edit(Request $request)
    {
        return view('requests.edit', compact($request));
    }

    /**
     * request show
     *
     * @param Request $request
     * @param ProcessRequest $instance
     * @return \Illuminate\Contracts\View|\Illuminate\View\View
     */
    public function show(Request $httpRequest, ProcessRequest $request)
    {
        return view('requests.show', ['instance' => $request]);
    }
}
