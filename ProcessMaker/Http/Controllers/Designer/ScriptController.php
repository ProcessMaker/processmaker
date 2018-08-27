<?php

namespace ProcessMaker\Http\Controllers\Designer;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Script;
use ProcessMaker\Model\Process;

class ScriptController extends Controller
{
    /**
     * Show the script editor
     *
     * @param Process $process
     * @param Form $form
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Process $process = null, Script $script = null)
    {
        if ($process->id !== $script->process_id) {
            request()->session()->flash('_alert', json_encode(['danger', __('The script does not belong to process.')]));
            // @todo  This should actually redirect to designer url
            return view('designer.designer', compact('process'));
        }
        return view('designer.script', compact(['process', 'script']));
    }

}