<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Support\Facades\Session;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Package's controller
 * Class PackageController
 * @package ProcessMaker\Http\Controllers\Admin
 */
class PackageController extends Controller
{
    public function index(Request $request)
    {
        //dd(Session::all());
        return view('admin.packages.index');
    }
}
