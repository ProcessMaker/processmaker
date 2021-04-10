<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Exception\ReferentialIntegrityException;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Http\Resources\ApiCollection;
use ProcessMaker\Http\Resources\Users as UserResource;
use ProcessMaker\Managers\LdapManager;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;

class LdapController extends Controller
{

    public function getGroups(Request $request)
    {
        $query = Setting::query();
        $query->where('group', 'LDAP');
        $query->notHidden();
        $settings = $query->get()->all();
        $settingsLoaded = [];
        foreach ($settings as $setting) {
            $settingsLoaded[$setting['key']] = $setting['config'];
        }

        $manager = new LdapManager();
        $response = $manager->searchGroups($settingsLoaded);
        return $response;
    }


    public function getUsers(Request $request)
    {
        $query = Setting::query();
        $query->where('group', 'LDAP');
        $query->notHidden();
        $settings = $query->get()->all();
        $settingsLoaded = [];
        foreach ($settings as $setting) {
            $settingsLoaded[$setting['key']] = $setting['config'];
        }
        $manager = new LdapManager();
        $response = $manager->searchUsersByGroup(
            $settingsLoaded,
            [
                'GRP_LDAP_DN' => $request->input('GROUP_DN'),
                'GRP_UID' => $request->input('GROUP_UID')
            ]);
        return $response;
    }
}
