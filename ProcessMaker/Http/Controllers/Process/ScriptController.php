<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class ScriptController extends Controller
{
     /**
     * Get the list of environment variables
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('processes.scripts.index');
    }

    /**
   * Edit a specific script
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function edit()
   {
     return view('processes.scripts.edit');
   }
}
