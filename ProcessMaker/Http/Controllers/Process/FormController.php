<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class FormController extends Controller
{
     /**
     * Get the list of environment variables
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('processes.forms.index');
    }
}
