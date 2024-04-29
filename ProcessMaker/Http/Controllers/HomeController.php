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
            // If is mobile redirect to view mobile request
            if ($isMobile) {
                return redirect('/requests');
            }
            // Redirect to home dynamic only if the package was enable
            if (!$isMobile && hasPackage('package-dynamic-ui')) {
                $user = \Auth::user();
                $homePage = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::getHomePage($user);

                return redirect($homePage);
            }

            // Redirect to the default view
            return redirect('/requests');
        }
    }

    public function redirectToIntended()
    {
        $url = request()->cookie('processmaker_intended');
        if ($url) {
            return redirect($url)->withCookie(\Cookie::forget('processmaker_intended'));
        }

        return redirect()->route('requests.index');
    }
}
