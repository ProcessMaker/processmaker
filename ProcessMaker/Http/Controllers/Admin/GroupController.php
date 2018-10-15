<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;

class GroupController extends Controller
{
  /**
   * Get the list of groups.
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
    return view('admin.groups.index');
  }

  /**
   * Get a specific group
   *
   
   */
   public function edit(Group $group)
   {
     $group = Group::find($group->uuid_text);
     return view('admin.groups.edit', compact('group'));
   }
   public function update(Group $group)
   {
     
   }
   public function show(Group $group)
   {
       return view('admin.groups.show', compact('group'));
   }
}
