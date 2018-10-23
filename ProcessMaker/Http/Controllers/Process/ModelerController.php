<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Process;

class ModelerController extends Controller
{

    public function __invoke(Process $process)
    {
        return view('processes.modeler.index', [
            'process' => $process
        ]);
    }
}