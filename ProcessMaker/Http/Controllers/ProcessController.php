<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;

class ProcessController extends Controller
{
    public function index()
    {
        return view('process.index');
    }
}
