<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\JsonData;
use ProcessMaker\i18nHelper;
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
        $status = [__('ACTIVE'), __('INACTIVE')];

        $langs = ['en'];
        if (app()->getProvider(\ProcessMaker\Package\Translations\PackageServiceProvider::class)) {
            $langs = i18nHelper::availableLangs();
        }
        // Our form controls need attribute:value pairs sot we convert the langs array to and associative one
        $availableLangs = array_combine($langs,$langs);

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

        $addons = $this->getPluginAddons('dynamic-homepage-edit', []);

        return view('profile.edit',
            compact('currentUser', 'states', 'timezones', 'countries', 'datetimeFormats', 'availableLangs',
                'status', 'addons'));
    }

    /**
     * show other users profile
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('profile.show', compact('user'));
    }
}
