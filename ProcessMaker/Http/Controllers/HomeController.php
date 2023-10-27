<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Helpers\MobileHelper;
use ProcessMaker\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            $isMobile = (
                isset($_SERVER['HTTP_USER_AGENT']) && MobileHelper::isMobile($_SERVER['HTTP_USER_AGENT'])
            ) ? true : false;
            if (!$isMobile && class_exists(\ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::class)) {
                $user = \Auth::user();
                $homePage = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::getHomePage($user);

                return redirect($homePage);
            }

            return redirect('/home');
        }
    }
}
