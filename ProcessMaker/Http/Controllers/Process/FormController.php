<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Form;

class FormController extends Controller
{
    /**
     * Get the list of forms
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('processes.forms.index');
    }

    /**
     * Get page edit
     *
     * @param Form $form
     *
     * @return Factory|View
     */
    public function edit(Form $form)
    {
        return view('processes.forms.edit',compact('form'));
    }
}
