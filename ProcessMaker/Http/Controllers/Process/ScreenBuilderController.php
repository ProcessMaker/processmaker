<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Managers\ScreenBuilderManager;

class ScreenBuilderController extends Controller
{
    /**
     * Get the screen in the constructor to edit it.
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function edit(ScreenBuilderManager $manager, Screen $screen)
    {
        /**
         * Emit the ModelerStarting event, passing in our ModelerManager instance. This will 
         * allow packages to add additional javascript for modeler initialization which
         * can customize the modeler controls list.
         */
        event(new ScreenBuilderStarting($manager, $screen->type));

        return view('processes.screen-builder.screen', compact('screen', 'manager'));
    }

}
