<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Events\PackageEvent;
use ProcessMaker\Http\Resources\ApiCollection;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = event(new PackageEvent(collect([])));
        $packages = collect($packages);
        $packages = $packages->merge([
            [
                "name" => "processmaker/advanced-dashboard",
                "description" => "Advanced ProcessMaker Dashboard",
                "version" => "2.5.1",
                "expire_in" => 1545099999
            ],
            [
                "name" => "processmaker/sla-reports",
                "description" => "ProcessMaker SLA Reports",
                "version" => "1.5.0",
                "expire_in" => 1548060999
            ],
            [
                "name" => "processmaker/batch-routing",
                "description" => "ProcessMaker Batch Routing Package",
                "version" => "1.4.40",
                "expire_in" => 1558060999
            ],
            [
                "name" => "processmaker/actions-by-email",
                "description" => "ProcessMaker Actions By Email Package",
                "version" => "3.1.1",
                "expire_in" => 1548040999
            ],

            [
                "name" => "processmaker/power-up",
                "description" => "ProcessMaker Power Up Package",
                "version" => "2.5.0",
                "expire_in" => 1548060999
            ],
            [
                "name" => "processmaker/multiple-input-uploader",
                "description" => "ProcessMaker Multiple Input Document Uploader",
                "version" => "2.5.0",
                "expire_in" => 1549060999
            ],
            [
                "name" => "processmaker/external-registration",
                "description" => "ProcessMaker External Registration",
                "version" => "2.0.40",
                "expire_in" => 1549560999
            ],
            [
                "name" => "processmaker/ftp-monitor",
                "description" => "ProcessMaker FTP Monitor",
                "version" => "1.4.40",
                "expire_in" => 1541500999
            ],
            [
                "name" => "processmaker/enterprise-search",
                "description" => "ProcessMaker Enterprise Search",
                "version" => "3.1.0",
                "expire_in" => null
            ]
        ]);
        if(!empty($request->get('filter'))){
            $packages = $packages->filter(function ($value) use ($request) {
                return strpos(data_get($value, 'name'), $request->get('filter')) ||
                    strpos(data_get($value, 'description'), $request->get('filter'));
            });
        }

        $page = $request->get('page');
        $perPage = $request->get('per_page');
        $order_by = 'name';
        $order_direction = 'asc';

        if($request->has('order_by'))
            $order_by = $request->get('order_by');

        if($request->has('order_direction'))
            $order_direction = $request->get('order_direction');

        if ($order_direction == 'asc')
            $packages = collect($packages->sortBy($order_by)->values()->all());
        else
            $packages = collect($packages->sortByDesc($order_by)->values()->all());

        $response = new LengthAwarePaginator($packages->forPage($page, $perPage), $packages->count(), $perPage, $page, ['path'=>url('api/1.0/packages')]);
        return new ApiCollection($response);
    }
}
