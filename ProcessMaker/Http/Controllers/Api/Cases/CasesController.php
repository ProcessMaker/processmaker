<?php

namespace ProcessMaker\Http\Controllers\Api\Cases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Application;
use ProcessMaker\Transformers\ApplicationTransformer;
use League\Fractal\Serializer\JsonApiSerializer;
// use Illuminate\Contracts\Pagination\IlluminatePaginatorAdapter;

/**
 * API endpoint for VueJS data front end
 */
class CasesController extends Controller
{

  public function index(Request $request)
  {

    /*
    "total": 6,	Total number of cases found.
"start": 1,	Number where the list of cases begins. The default is 1.
"limit": 25,	Maximum number of cases returned. The default is 25.
"sort": "app_cache_view.app_number",	The field in the wf_<WORKSPACE>.APP_CACHE_VIEW table which is used to sort the cases. The default is "app_cache_view.app_number".
"dir": "desc",	The sort order of the cases, which can be "asc" (ascending) or "desc" (descending, which is the default).
"cat_uid": "",	The unique ID of a process category. Only cases whose processes are in the given category will be returned.
"pro_uid": "",	The unique ID of a process. Only cases in the given process will be returned.
"search": "",
*/

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

    $paginator = Application::orderBy($expected['sort'],$expected['sort_order']);

    if ($request->has('filter')) {
        $paginator->where('APP_TITLE', 'LIKE', '%' . $request->get('filter') . '%');
        $paginator->where('APP_DESCRIPTION', 'LIKE', '%' . $request->get('filter') . '%');
        $paginator->where('APP_NUMBER', 'LIKE', '%' . $request->get('filter') . '%');
    }

    if ($request->has('status')) {
        $paginator->where('APP_STATUS', $request->get('status'));
    }

    if ($request->has('per_page')) {

      $expected['per_page'] = (int) $request->get('per_page');

    }

    $paginator->paginate($expected['per_page']);

    $accounts = new Collection($paginator->items(), new ApplicationTransformer($paginator));

    $paginator->setPaginator(new IlluminatePaginatorAdapter($paginator));

    $accounts = $this->fractal->createData($accounts); // Transform data

    return $accounts->toArray();

  }

}
