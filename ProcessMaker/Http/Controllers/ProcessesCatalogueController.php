<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class ProcessesCatalogueController extends Controller
{
    public function index(Request $request)
    {
        return view('processes-catalogue.index');
    }
}
