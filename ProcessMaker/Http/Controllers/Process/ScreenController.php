<?php

namespace ProcessMaker\Http\Controllers\Process;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Screen;

class ScreenController extends Controller
{
    /**
     * Get the list of screens
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('processes.screens.index');
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
