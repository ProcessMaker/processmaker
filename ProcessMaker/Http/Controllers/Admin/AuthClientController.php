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
        return view('admin.auth-clients.index');
    }
}
