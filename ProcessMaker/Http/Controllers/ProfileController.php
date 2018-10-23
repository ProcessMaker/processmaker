<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;

class ProfileController extends Controller
{
    /**
   * edit your profile.
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function edit()
  {
      $current_user = \Auth::user();
      return view('profile.edit', compact('current_user'));
  }
    /**
   * show other profile
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function show(User $user)
   {
     return view('profile.show',compact($user));
   }


}
