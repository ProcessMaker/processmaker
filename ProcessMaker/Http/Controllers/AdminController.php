<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $permission = \Auth::user()->canAnyFirst(
            'view-users|view-groups|view-auth_clients|manage-public-files|view-settings|view-plugins',
        );

        $routesByPermission = [
            'view-users' => 'users.index',
            'view-groups' => 'groups.index',
            'view-auth_clients' => 'auth-clients.index',
            'manage-public-files' => 'file-manager.index',
            'view-settings' => 'settings.index',
            'view-plugins' => 'plugins.index',
        ];

        $route = $routesByPermission[$permission] ?? null;

        if ($route === null) {
            throw new AuthorizationException();
        }

        return redirect()->route($route);
    }
}
