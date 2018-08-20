<?php

namespace ProcessMaker\Http\Controllers\Designer;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;

class ProcessController extends Controller
{

    /**
     * Get the list task
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('processes.index');
    }


    /**
     * Redirects to the view of the designer
     *
     * @param string $process
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show($process = null)
    {
        $model = Process::where('uid', '=', $process)->first();

        if (!$model) {
            request()->session()->flash('_alert', json_encode(['danger', __('The process was not found.')]));
            return redirect('processes');
        }
        $title = $model->name;
        return view('designer.designer', ['process' => $model, 'title' => $title]);
    }
}
