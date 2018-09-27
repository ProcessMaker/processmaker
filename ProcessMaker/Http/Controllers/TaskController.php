<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class TaskController extends Controller
{
    public function index()
  {
      return view('tasks.index');
  }
//     public function edit(Tasks $tasks) /*<-----*/ // 
//   {
//     return view('tasks.edit',compact($tasks));  //is the edit page added to routes?
//   }
//   public function create()
//   {
//     return view('tasks.create');
//   }
}
