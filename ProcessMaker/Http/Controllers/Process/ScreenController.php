<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;

class ScreenController extends Controller
{
    /**
     * Get the list of screens
     *
     * @return Factory|View
     */
    public function index()
    {
        $types = [
            '' => __('Select Type'),
            'DISPLAY' => 'Display',
            'FORM' => 'Form',
        ];

        if (Script::where('key', 'processmaker-communication-email-send')->exists()) {
            $types['EMAIL'] = 'Email';
        }
        return view('processes.screens.index', compact('types'));
    }

    /**
     * Get page edit
     *
     * @param Screen $screen
     *
     * @return Factory|View
     */
    public function edit(Screen $screen)
    {
        return view('processes.screens.edit', compact('screen'));
    }
}
