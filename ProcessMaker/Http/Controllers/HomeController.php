<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public $skipPermissionCheckFor = ['index'];

    public function index(Request $request)
    {

      if (Auth::check()) {
          return redirect('/requests');
      }

      return redirect('/login');

    }
}
