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
            // Redirect to home dynamic only if the package was enable
            if (hasPackage('package-dynamic-ui')) {
                $user = Auth::user();

                // Check if there is at least one custom dashboard per user
                $customDashboardExists = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::where('type', 'DASHBOARD')
                    ->where('assignable_id', $user->id)
                    ->count() > 0;

                if ($customDashboardExists) {
                    $homePage = \ProcessMaker\Package\PackageDynamicUI\Models\DynamicUI::getHomePage($user);

                    return redirect($homePage);
                }
            }

            // Redirect to the default view
            return redirect('/tasks');
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
