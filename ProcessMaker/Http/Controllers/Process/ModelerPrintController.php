<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Models\Process;

class ModelerPrintController extends Controller
{
    public function __invoke(Process $process)
    {
        return view('processes.modeler.print', [
            'process' => $process
        ]);
    }
}
