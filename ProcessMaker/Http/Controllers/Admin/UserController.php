<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
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
      return view('admin.users.index');
  }

  public function edit(User $user)
  {
    return view('admin.users.edit',compact($user));
  }

  public function create()
  {
    return view('admin.users.create');
  }
}
