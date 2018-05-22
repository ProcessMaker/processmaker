<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

class TestController extends Controller
{
    public function index(Request $request)
    {

      // return $request;

      $tmp = new \ProcessMaker\Model\User;

      $field = 'USR_FIRSTNAME';

      $order = 'asc';

      if($request->sort){

        $sort = explode('|',$request->sort);

        $field = $sort[0];

        if(isset($sort[1]) && $sort[1] == 'desc'){

          $order = 'desc';

        }
      }
      return $tmp->orderBy($field,$order)
      ->where('USR_FIRSTNAME','LIKE','%'.$request->filter.'%')
      ->orWhere('USR_LASTNAME','LIKE','%'.$request->filter.'%')
      ->orWhere('USR_EMAIL','LIKE','%'.$request->filter.'%')
      ->paginate();

    }
    public function FlashMessageTest(session('message', 'testMessage'))
    {
      session()->flash('message','testMessage');
      return redirect()->home();
    }
}
