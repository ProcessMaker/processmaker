<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequest;

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

    /**
   * Edit a request
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function edit(Request $request)
   {
     return view('requests.edit',compact($request));
   }

    /**
   * request show 
   *
   * @return \Illuminate\View\View|\Illuminate\Contracts\View
   */
   public function show(Request $request)
   {
     return view('requests.show',compact($request));
   }
}
