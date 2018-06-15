<?php
namespace ProcessMaker\Http\Controllers\Management;

use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\User;

class GroupsController extends Controller
{

    /**
     * Get the list of groups.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        return view('management.groups.index');
    }
}
