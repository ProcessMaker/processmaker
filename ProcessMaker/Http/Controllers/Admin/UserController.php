<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class UserController extends Controller
{
    /**
     * Get the list of users.
     *
     * @return Factory|View
     */
    public function index()
    {
        $groups = $this->getAllGroups();
        return view('admin.users.index', compact(['groups']));
    }

    /**
     * @param User $user
     *
     * @return Factory|View
     */
    public function edit(User $user)
    {
        $user->memberships;
        $groups = $this->getAllGroups();
        return view('admin.users.edit', compact(['user', 'groups']));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function show()
    {
        return view('admin.users.show');
    }

    /**
     * Get all groups actives
     *
     * @return mixed
     */
    private function getAllGroups()
    {
        return Group::where('status', 'ACTIVE')->get();
    }
}
