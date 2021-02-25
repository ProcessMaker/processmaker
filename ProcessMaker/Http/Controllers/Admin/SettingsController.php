<?php

namespace ProcessMaker\Http\Controllers\Admin;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Get the list of settings.
     *
     * @return Factory|View
     */
    public function index()
    {
        if (Setting::notHidden()->count()) {
            return view('admin.settings.index');
        } else {
            abort(404);
        }
    }
}
