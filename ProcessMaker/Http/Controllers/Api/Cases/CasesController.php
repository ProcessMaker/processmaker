<?php

namespace ProcessMaker\Http\Controllers\Api\Cases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;

/**
 * API endpoint for VueJS data front end
 */
class CasesController extends Controller
{

  public function index(Request $request)
  {

    $expected = [
      'per_page' => 15,
      'page' => 1,
      'sort' => 'APP_NUMBER',
      'sort_order' => 'asc',
      'per_page' => 15,
      'APP_STATUS' => 1
    ];


    if ($request->has('sort')) {

      $sort = explode('|',$request->sort);

      $possible_values = ['APP_TITLE','APP_DESCRIPTION','APP_NUMBER'];

      if(in_array($sort[0],$possible_values)){

        $expected['sort'] = $sort[0];

      }

      if(isset($sort[1]) && $sort[1] == 'desc'){

        $expected['sort_order'] = 'desc';

      }

    }

    $query = Application::orderBy($expected['sort'],$expected['sort_order']);

    if ($request->has('filter')) {
        $query->where('APP_TITLE', 'LIKE', '%' . $request->get('filter') . '%');
        $query->where('APP_DESCRIPTION', 'LIKE', '%' . $request->get('filter') . '%');
        $query->where('APP_NUMBER', 'LIKE', '%' . $request->get('filter') . '%');
    }

    if ($request->has('status')) {
        $query->where('APP_STATUS', $request->get('status'));
    }

    if ($request->has('per_page')) {

      $expected['per_page'] = (int) $request->per_page;

    }

    return $query->paginate($expected['per_page']);

  }

}
