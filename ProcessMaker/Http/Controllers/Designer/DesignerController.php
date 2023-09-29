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
        $redirect = $this->checkAuth();
        if ($redirect) {
            return redirect()->route($redirect);
        }

        return view('designer.index');
    }

    private function checkAuth()
    {
        $perm = 'view-processes|
            view-process-categories|
            view-scripts|
            view-screens|
            view-environment_variables|
            view-projects';
        switch (Auth::user()->canAnyFirst($perm)) {
            case 'view-processes':
                return false; // already on index, continue with it
            case 'view-process-categories':
                return 'process-categories.index';
            case 'view-scripts':
                return 'scripts.index';
            case 'view-screens':
                return 'screens.index';
            case 'view-environment_variables':
                return 'environment-variables.index';
            case 'view-projects':
                return 'projects.index';
            default:
                throw new AuthorizationException();
        }
    }
}
