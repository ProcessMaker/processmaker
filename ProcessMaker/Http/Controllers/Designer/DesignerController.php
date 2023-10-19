<?php

namespace ProcessMaker\Http\Controllers\Designer;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Traits\HasControllerAddons;

class DesignerController extends Controller
{
    use HasControllerAddons;

    /**
     * Get initial data for Designer Home Page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $listConfig = (object) [
            'status' => $request->input('status'),
        ];

        return view('designer.index', compact('listConfig'));
    }
}
