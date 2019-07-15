<?php

namespace ProcessMaker\Http\Controllers\Admin;

use ProcessMaker\Http\Controllers\Controller;

class AuthClientController extends Controller
{
    /**
     * List auth clients
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        $password_grant_client = \Laravel\Passport\Client::where('password_client', 1)->first();
        return view('admin.auth-clients.index', compact('password_grant_client'));
    }
}
