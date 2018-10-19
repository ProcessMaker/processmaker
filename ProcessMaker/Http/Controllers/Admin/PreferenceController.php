<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\JsonData;

class PreferenceController extends Controller
{
  /**
   * Get the preferences form
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
      $timezones = JsonData::timezones();
      return view('admin.preferences.index', compact('timezones'));
  }
}
