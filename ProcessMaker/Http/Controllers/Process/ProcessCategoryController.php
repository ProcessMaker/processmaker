<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class ProcessCategoryController extends Controller
{
    public function index() {
        return view ('processes.categories.index');
    }
}
