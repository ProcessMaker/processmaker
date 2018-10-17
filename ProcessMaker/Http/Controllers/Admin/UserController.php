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
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
    $groups = array();
    $groups_from_DB = Group::all()->toArray();
    foreach( $groups_from_DB as $group){
      $group_uuid = $group['uuid'];
      $group_name = $group['name'];
      array_push($groups, array('label' => $group_name, 'id' => $group_uuid));
    };
    
    return view('admin.users.index', compact('groups'));
  }
  
  public function edit(User $user)
  {
    return view('admin.users.edit', compact('user'));
  }
  
  public function show(User $user)
  {
    return view('admin.users.show', compact('user'));
  }
  
  public function create()
  {
      return view('admin.users.create');
  }

}
