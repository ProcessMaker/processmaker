<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class PluginController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.plugins.index');
    }
}
