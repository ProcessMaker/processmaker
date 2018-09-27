<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;

class ProfileController extends Controller
{
    /**
   * Get your profile.
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index(User $user)
  {
      return view('profile.index');
  }
    /**
   * Edit your profile
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function edit(User $user)
   {
     return view('profile.edit',compact($user));
   }
    /**
   * show other users profile
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function show(User $user)
   {
     return view('profile.show',compact($user));
   }

}
