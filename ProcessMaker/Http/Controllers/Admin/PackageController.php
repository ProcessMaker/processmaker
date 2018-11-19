<?php

namespace ProcessMaker\Http\Controllers\Admin;

use ProcessMaker\Http\Controllers\Controller;

class PackageController extends Controller
{
    public function index()
    {
        return view('admin.packages.index');
    }
}
