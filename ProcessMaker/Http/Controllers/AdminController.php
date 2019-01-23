<?php

namespace ProcessMaker\Http\Controllers;

use ProcessMaker\Models\Group;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\Process;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessPermission;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
 
class AdminController extends Controller
{
 public function dashboard(){
        return view('admin.dashboard');
    }
}