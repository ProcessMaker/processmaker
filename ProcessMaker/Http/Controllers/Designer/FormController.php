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
        if ($process->id !== $form->process_id) {
            request()->session()->flash('_alert', json_encode(['danger', __('The form does not belong to process.')]));
            // @todo  This should actually redirect to designer url
            return view('designer.designer', compact('process'));
        }
        return view('designer.form', compact(['process', 'form']));
    }

}