<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Form;

class FormBuilderController extends Controller
{
    /**
     * Get the form in the constructor to edit it.
     *
     * @param Form $form
     *
     * @return Factory|View
     */
    public function edit(Form $form)
    {
        return view('processes.form-builder.form', compact('form'));
    }

}
