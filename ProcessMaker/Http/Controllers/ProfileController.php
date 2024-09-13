<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\i18nHelper;
use ProcessMaker\Models\JsonData;
use ProcessMaker\Models\User;
use ProcessMaker\Package\Auth\Models\SsoUser;
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

        $langs = ['en'];
        if (app()->getProvider(\ProcessMaker\Package\Translations\PackageServiceProvider::class)) {
            $langs = i18nHelper::availableLangs();
        }
        // Our form controls need attribute:value pairs sot we convert the langs array to and associative one
        $availableLangs = array_combine($langs, $langs);

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

        $ssoUser = false;
        if (class_exists(SsoUser::class)) {
            $ssoUser = SsoUser::where('user_id', $user->id)->exists();
        }

        // Get global and valid 2FA preferences for the user
        $enabled2FA = config('password-policies.2fa_enabled', false);
        $global2FAEnabled = config('password-policies.2fa_method', []);
        $currentUser->preferences_2fa = $currentUser->getValid2FAPreferences();
        $is2FAEnabledForGroup = $currentUser->in2FAGroupOrIndependent();

        $addons = $this->getPluginAddons('edit', []);

        return view('profile.edit',
            compact('currentUser', 'states', 'timezones', 'countries', 'datetimeFormats', 'availableLangs',
                'status', 'enabled2FA', 'global2FAEnabled', 'is2FAEnabledForGroup', 'addons', 'ssoUser'));
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
