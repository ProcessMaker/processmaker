<?php

namespace ProcessMaker\Http\Controllers\Designer;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Composer;
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
        $hasPackage = false;
        if (class_exists(\ProcessMaker\Package\Projects\Models\Project::class)) {
            $hasPackage = true;
        }

        $listConfig = (object) [
            'status' => $request->input('status'),
            'hasPackage' => $hasPackage,
        ];

        $currentUser = Auth::user()->only(['id', 'username', 'fullname', 'firstname', 'lastname', 'avatar']);

        return view('designer.index', compact('listConfig', 'currentUser'));
    }
}
