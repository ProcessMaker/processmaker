<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use ProcessMaker\Http\Controllers\Controller;

class CasesRetentionController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.cases-retention.index');
    }
}
