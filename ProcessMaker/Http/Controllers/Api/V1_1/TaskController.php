<?php

namespace ProcessMaker\Http\Controllers\Api\V1_1;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\ProcessRequestToken;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProcessRequestToken::select('id');

        return $query->paginate();
    }
}
