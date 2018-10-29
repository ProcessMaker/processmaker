<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User as User;

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
   * show other users profile
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function show($id)
   {

     $user = User::findOrFail($id);

     return view('profile.show',compact('user'));
   }

}
