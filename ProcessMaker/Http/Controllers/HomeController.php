<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            if (class_exists(\ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::class)) {
                $user = \Auth::user();
                $homePage = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::getHomePage($user);

                return redirect($homePage);
            }

            return redirect('/requests');
        }
    }
}
