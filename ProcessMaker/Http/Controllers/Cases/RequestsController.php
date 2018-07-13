<?php
namespace ProcessMaker\Http\Controllers\Cases;

use ProcessMaker\Http\Controllers\Controller;

class RequestsController extends Controller
{

    /**
     * Get the list of users.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('cases.requests.index');
    }
}
