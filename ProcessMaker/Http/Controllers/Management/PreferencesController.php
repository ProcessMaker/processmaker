<?php
namespace ProcessMaker\Http\Controllers\Management;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;

class PreferencesController extends Controller
{

    /**
     * Get the list of users.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('preferences');
    }
    public function show()
    {
        return view('management.themes.index');
    }
}
