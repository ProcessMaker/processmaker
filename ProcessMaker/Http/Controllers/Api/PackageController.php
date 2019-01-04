<?php

namespace ProcessMaker\Http\Controllers\Api;

use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Events\PackageEvent;
use ProcessMaker\Http\Resources\ApiCollection;

class PackageController extends Controller
{
    /**
     * Returns a collected list from the event of the every ProcessMaker installed packages
     * @param Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {

        $packages = event(new PackageEvent(collect([])));
        $packages = collect($packages);

        if(!empty($request->get('filter'))){
            $packages = $packages->filter(function ($value) use ($request) {
                $search = strtolower($request->get('filter'));
                $by_name = strtolower(data_get($value, 'name'));
                $by_friendly_name = strtolower(data_get($value, 'friendly_name'));
                $by_description = strtolower(data_get($value, 'description'));
                return  strpos($by_name, $search) ||
                        strpos($by_friendly_name, $search) ||
                        strpos($by_description, $search);
            });
        }

        $page = $request->get('page');
        $perPage = $request->get('per_page');
        $order_by = $request->has('order_by') ? $order_by = $request->get('order_by') : 'name';
        $order_direction = $request->has('order_direction') ? $request->get('order_direction') : 'asc';

        if ($order_direction == 'asc')
            $packages = collect($packages->sortBy($order_by)->values()->all());
        else
            $packages = collect($packages->sortByDesc($order_by)->values()->all());

        $response = new LengthAwarePaginator($packages->forPage($page, $perPage), $packages->count(), $perPage, $page, ['path'=>url('api/1.0/packages')]);
        return new ApiCollection($response);
    }
}
