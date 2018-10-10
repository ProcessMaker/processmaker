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
    $user = new User;
    return view('admin.users.index', compact('user'));
    
  }

  public function store(Request $request)
  {
    $user = User::create($request->all());
    return redirect('admin/users/'.$user->uuid_text);
  }

  public function edit(User $user)
  {
    return view('admin.users.edit', compact('user'));
  }

  public function show(User $user)
  {
    return view('admin.users.show', compact('user'));
  }
}
