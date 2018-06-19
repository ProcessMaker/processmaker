<?php
namespace ProcessMaker\Http\Controllers\Management;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;

class RolesController extends Controller
{

    /**
     * Get the list of roles.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('management.roles.index');
    }
}
