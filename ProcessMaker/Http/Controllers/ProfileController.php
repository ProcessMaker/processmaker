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
      return view('profile.edit');
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
