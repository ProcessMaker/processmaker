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
        return view('requests.index');
    }

    /**
     * Actions to execute after a request was submitted.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function requestSubmitted()
    {
        // We set the alert message and type in the session, so the next page will show it
        Session::put('_alert', '["success", "The request was completed successfully."]');

        // Redirect to the tasks list
        return redirect('/tasks');
    }
}
