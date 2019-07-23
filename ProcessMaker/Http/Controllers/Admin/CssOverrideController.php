<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class CssOverrideController extends Controller
{
    public function edit()
    {
        return view('admin.cssOverride.edit');
    }
}
