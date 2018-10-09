<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
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
      return view('admin.users.index');
  }

    /**
     * @param User $user
     *
     * @return Factory|View
     */
  public function edit(User $user)
  {
    return view('admin.users.edit',compact('user'));
  }

  public function create()
  {
    return view('admin.users.create');
  }
  public function show()
  {
    return view('admin.users.show');
  }
}
