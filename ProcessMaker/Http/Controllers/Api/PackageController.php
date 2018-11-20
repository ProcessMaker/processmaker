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

        // this dummy data of the packages is temporally because to demo
        $packages = $packages->merge([
            [
                "name" => "processmaker/advanced-dashboard",
                "friendly_name" => "Advanced Dashboards",
                "description" => "Advanced ProcessMaker Dashboard",
                "version" => "2.5.1",
                "expire_in" => Carbon::createFromTimestamp(1543498642)->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/sla-reports",
                "friendly_name" => "SLA Report",
                "description" => "ProcessMaker SLA Reports",
                "version" => "1.5.0",
                "expire_in" => Carbon::createFromTimestamp(1548060999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/batch-routing",
                "friendly_name" => "Batch Routing",
                "description" => "ProcessMaker Batch Routing Package",
                "version" => "1.4.40",
                "expire_in" => Carbon::createFromTimestamp(1558060999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/actions-by-email",
                "friendly_name" => "Actions by Email",
                "description" => "ProcessMaker Actions By Email Package",
                "version" => "3.1.1",
                "expire_in" => Carbon::createFromTimestamp(1548040999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/power-up",
                "friendly_name" => "Power Up",
                "description" => "ProcessMaker Power Up Package",
                "version" => "2.5.0",
                "expire_in" => Carbon::createFromTimestamp(1548060999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/multiple-input-uploader",
                "friendly_name" => "Multiple Input Document Uploader",
                "description" => "ProcessMaker Multiple Input Document Uploader",
                "version" => "2.5.0",
                "expire_in" => Carbon::createFromTimestamp(1549060999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/external-registration",
                "friendly_name" => "External Registration",
                "description" => "ProcessMaker External Registration",
                "version" => "2.0.40",
                "expire_in" => Carbon::createFromTimestamp(1549560999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/ftp-monitor",
                "friendly_name" => "FTP Monitor",
                "description" => "ProcessMaker FTP Monitor",
                "version" => "1.4.40",
                "expire_in" => Carbon::createFromTimestamp(1541500999, config('app.timezone'))->format(DateTime::ISO8601)
            ],
            [
                "name" => "processmaker/enterprise-search",
                "friendly_name" => "Enterprise Data Search",
                "description" => "ProcessMaker Enterprise Search",
                "version" => "3.1.0",
                "expire_in" => null
            ]
        ]);
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
