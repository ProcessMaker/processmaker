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
     * request show
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function show(ProcessRequest $request)
    {
        $request->participants;
        $request->user;
        $request->summary = $request->summary();
        return view('requests.show', compact('request'));
    }
}
