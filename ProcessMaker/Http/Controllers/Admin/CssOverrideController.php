<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class CssOverrideController extends Controller
{
    public function edit()
    {

        $config = Setting::byKey('css-override');
        return view('admin.cssOverride.edit', compact('config'));
    }
}
