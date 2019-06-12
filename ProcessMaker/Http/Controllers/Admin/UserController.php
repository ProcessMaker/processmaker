<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\JsonData;
use ProcessMaker\Models\User;
use ProcessMaker\Models\Permission;

class UserController extends Controller
{
    /**
     * Get the list of users.
     *
     * @return Factory|View
     */
    public function index()
    {
        //load groups
        $groups = $this->getAllGroups();
        return view('admin.users.index', compact(['groups']));
    }

    /**
     * @param User $user
     *
     * @return Factory|View
     */
    public function edit(User $user)
    {
        //include memberships
        $user->memberships = $user->groupMembersFromMemberable()->get();
        $groups = $this->getAllGroups();
        $all_permissions = Permission::all();
        $permissionNames = $user->permissions()->pluck('name')->toArray();
        $permissionGroups = $all_permissions->sortBy('title')->groupBy('group')->sortKeys();

        $availableLangs = [];
        foreach (scandir(resource_path('lang')) as $file) {
            preg_match("/([a-z]{2})\.json/", $file, $matches);
            if (!empty($matches)) {
                $availableLangs[] = $matches[1];
            }
        }
        $currentUser = $user;
        $states = JsonData::states();
        $countries = JsonData::countries();

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

        // Default values if the users doesn't have locale configuration
        $appTimezone = getenv('APP_TIMEZONE') !== false ? getenv('APP_TIMEZONE') : 'America/Los_Angeles';
        $appFormat = getenv('DATE_FORMAT') !== false ? getenv('DATE_FORMAT') : 'm/d/Y H:i';
        $appLanguage = getenv('APP_LANG') !== false ? getenv('APP_LANG') : 'en';
        $user->timezone  = $user->timezone ?? $appTimezone;
        $user->datetime_format  = $user->datetime_format ?? $appFormat;
        $user->language  = $user->language ?? $appLanguage;

        return view('admin.users.edit', compact(
            'user',
            'groups',
            'all_permissions',
            'permissionNames',
            'permissionGroups',
            'states',
            'timezones',
            'appFormat',
            'appTimezone',
            'appLanguage',
            'countries',
            'datetimeFormats',
            'availableLangs'
        ));
    }

    /**
     * Get all groups actives
     *
     * @return mixed
     */
    private function getAllGroups()
    {
        return Group::where('status', 'ACTIVE')->get();
    }
}
