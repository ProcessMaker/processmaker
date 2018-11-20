<?php

namespace ProcessMaker\Http\Controllers\Admin;

use ProcessMaker\Http\Controllers\Controller;

/**
 * Package's controller
 * Class PackageController
 * @package ProcessMaker\Http\Controllers\Admin
 */
class PackageController extends Controller
{
    public function index()
    {
        return view('admin.packages.index');
    }
}
