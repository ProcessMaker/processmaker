<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

class ProcessesCatalogueController extends Controller
{
    public function index(Request $request)
    {
        return view('processes-catalogue.index');
    }

    /**
     * @param Process $process
     * @param string $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function open(Process $process, $id)
    {
        return view('processes-catalogue.open', compact('id'));
    }
}
