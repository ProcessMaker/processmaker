<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class ScriptExecutorController extends Controller
{
    public function index(Request $request)
    {
        if (!config('app.custom_executors')) {
            abort(404);
        }

        return view('admin.script-executors.index');
    }
}
