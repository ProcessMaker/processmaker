<?php

namespace ProcessMaker\Http\Controllers\Api;

use DB;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\SecurityLog;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\ApiResource;

class SecurityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SecurityLog::query();
        
        if ($pmql = $request->input('pmql')) {
            $query->pmql($pmql);
        }
        
        if ($filter = $request->input('filter')) {
            $filter = '%' . mb_strtolower($filter) . '%';
            $query->where('event', 'like', $filter)
                  ->orWhere(DB::raw("LOWER(ip)"), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.browser.name')"), 'like', $filter)
                  ->orWhere(DB::raw("LOWER(meta->>'$.os.name')"), 'like', $filter);
        }
        
        if ($orderBy = $request->input('order_by')) {
            $orderBy = DB::raw(preg_replace('/\.(.+)/', "->>'\$.$1'", $orderBy, 1));
            
            $orderDirection = $request->input('order_direction');
            
            if (! $orderDirection) {
                $orderDirection = 'asc';
            }
            
            $query->orderBy($orderBy, $orderDirection);
        }
        
        $response = $query->get();
        
        return new ApiCollection($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SecurityLog $securityLog)
    {
        return new ApiResource($securityLog);
    }
}
