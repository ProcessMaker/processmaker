<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Models\User;
use ProcessMaker\Models\JsonData;
use ProcessMaker\i18nHelper;


class ProfileController extends Controller
{
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

        return view('profile.edit',
            compact('currentUser', 'states', 'timezones', 'countries', 'datetimeFormats', 'availableLangs'));
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
