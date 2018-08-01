<?php

namespace ProcessMaker\Http\Controllers\Designer;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;

class FormController extends Controller
{
    /**
     * Get the Definition form
     *
     * @param Process $process
     * @param Form $form
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Process $process = null, Form $form = null)
    {
        if (!$process) {
            request()->session()->flash('_alert', json_encode(['danger', __('The process was not found.')]));
            return view('processes.index');
        }
        if (!$form) {
            request()->session()->flash('_alert', json_encode(['danger', __('The form was not found.')]));
            return view('designer.designer', compact('process'));
        }
        //return view('designer.form', ['process' => $process, 'form' => $form]);
        return view('designer.form', compact(['process', 'form']));
    }

}