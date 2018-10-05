<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class PreferenceController extends Controller
{
  /**
   * Get the preferences form
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
      return view('admin.preferences.index');
  }
}
