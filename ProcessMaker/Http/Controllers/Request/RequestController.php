<?php

namespace ProcessMaker\Http\Controllers\Request;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class RequestController extends Controller
{
   /**
   * Get the list of requests.
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
  public function index()
  {
      return view('requests.index');
  }
}
