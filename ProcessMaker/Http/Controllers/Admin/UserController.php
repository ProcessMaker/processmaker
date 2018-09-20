<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\User;

class UserController.php extends Controller
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

  public function show(User $user)
  {
    return view('admin.users.edit',compact($user));
  }
}
