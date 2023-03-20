<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Events\ScreenBuilderStarting;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\ScreenBuilderManager;
use ProcessMaker\Models\Screen;

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

        $autoSaveDelay = config('versions.delay.process', 5000);
        $draft = $screen->versions()->draft()->first();
        if ($draft) {
            $screen->fill($draft->only([
                'config',
                'computed',
                'custom_css',
                'watchers',
            ]));
        }

        return view('processes.screen-builder.screen', compact('screen', 'manager', 'autoSaveDelay'));
    }
}
