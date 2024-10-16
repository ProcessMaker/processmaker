<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\i18nHelper;
use ProcessMaker\Models\JsonData;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasControllerAddons;

class ProfileController extends Controller
{
    use HasControllerAddons;

    /**
     * edit your profile.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function edit()
    {
        $currentUser = \Auth::user();
        $states = JsonData::states();
        $countries = JsonData::countries();
        $status = [
            ['value' => 'ACTIVE', 'text' => __('Active')],
            ['value' => 'INACTIVE', 'text' => __('Inactive')],
        ];

        $timezones = array_reduce(JsonData::timezones(),
            function ($result, $item) {
                $result[$item] = $item;

                return $result;
            }
        );

        $datetimeFormats = array_reduce(JsonData::datetimeFormats(),
            function ($result, $item) {
                $result[$item['format']] = $item['title'];

                return $result;
            }
        );

        // Get global and valid 2FA preferences for the user
        $enabled2FA = config('password-policies.2fa_enabled', false);
        $global2FAEnabled = config('password-policies.2fa_method', []);
        $currentUser->preferences_2fa = $currentUser->getValid2FAPreferences();
        $is2FAEnabledForGroup = $currentUser->in2FAGroupOrIndependent();

        $addons = $this->getPluginAddons('edit', []);

        return view('profile.edit',
            compact('currentUser', 'states', 'timezones', 'countries', 'datetimeFormats',
                'status', 'enabled2FA', 'global2FAEnabled', 'is2FAEnabledForGroup', 'addons'));
    }

    /**
     * show other users profile
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function show(Request $request, $id)
    {
        if (
            $request->user()->id !== intval($id) &&
            !$request->user()->can('view-other-users-profiles')
        ) {
            abort(404);
        }

        $user = User::findOrFail($id);

        return view('profile.show', compact('user'));
    }
}
