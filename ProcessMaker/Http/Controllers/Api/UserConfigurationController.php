<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiResource;
use ProcessMaker\Models\User;
use ProcessMaker\Models\UserConfiguration;

class UserConfigurationController extends Controller
{
    const DEFAULT_USER_CONFIGURATION = [
        'launchpad' => [
            'isMenuCollapse' => true,
        ],
        'cases' => [
            'isMenuCollapse' => true,
        ],
        'requests' => [
            'isMenuCollapse' => true,
        ],
        'tasks' => [
            'isMenuCollapse' => true,
        ],
    ];

    public function index()
    {
        $user = Auth::user();
        $query = UserConfiguration::select('user_id', 'ui_configuration');
        $query->userConfiguration($user->id);
        $response = $query->first();

        if (empty($response)) {
            $response = [
                'user_id' => $user->id,
                'ui_configuration' => json_encode(self::DEFAULT_USER_CONFIGURATION),
            ]; // return default
        }

        return new ApiResource($response);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $userConf = new UserConfiguration();
        $request->validate([
            'ui_configuration' => 'required|array',
            'ui_configuration.launchpad' => 'required|array',
            'ui_configuration.cases' => 'required|array',
            'ui_configuration.requests' => 'required|array',
            'ui_configuration.tasks' => 'required|array',
        ]);
        $uiConfiguration = json_encode($request->input('ui_configuration'));

        try {
            // Store the user configuration
            $userConf->updateOrCreate([
                'user_id' => $user->id,
            ], [
                'ui_configuration' => $uiConfiguration,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return new ApiResource($userConf->refresh());
    }
}
