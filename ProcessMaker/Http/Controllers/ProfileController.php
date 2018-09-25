<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;

class ProfileController extends Controller
{
   /**
   * Get the preferences form
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
      return view('profile.index');
  }

  public function show(User $user)
  {
    return view('profile.show',compact($user));
  }
}
