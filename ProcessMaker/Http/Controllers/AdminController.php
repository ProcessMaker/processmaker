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
        switch (\Auth::user()->canAnyFirst('view-users|view-groups|view-auth_clients|manage-public-files|view-settings')) {
            case 'view-users':
                return redirect()->route('users.index');
            case 'view-groups':
                return redirect()->route('groups.index');
            case 'view-auth_clients':
                return redirect()->route('auth-clients.index');
            case 'manage-public-files':
                return redirect()->route('file-manager.index');
            case 'view-settings':
                return redirect()->route('settings.index');
            default:
                throw new AuthorizationException();
        }
    }
}
