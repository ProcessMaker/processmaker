<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\User;

class GroupController extends Controller
{
  /**
   * Get the list of groups.
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
    $groups = Group::all();
    return view('admin.groups.index', compact('groups'));
  }

  /**
   * Get a specific group
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function edit(Group $group)
   {
     return view('admin.groups.edit', compact('group'));
   }

   public function show(Group $group) // show new process to UI
   {
       return view('admin.groups.show', compact('group'));  // from data item in index, once clicked, this page will show with ability to edit and destroy
   }
}
