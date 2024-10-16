<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Group;
use ProcessMaker\Models\JsonData;
use ProcessMaker\Models\Permission;
use ProcessMaker\Models\User;
use ProcessMaker\Traits\HasControllerAddons;

class UserController extends Controller
{
    use HasControllerAddons;

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

        $currentUser = $user;
        $states = JsonData::states();
        $countries = JsonData::countries();
        $status = [
            ['value' => 'ACTIVE', 'text' => __('Active')],
            ['value' => 'INACTIVE', 'text' => __('Inactive')],
            ['value' => 'BLOCKED', 'text' => __('Blocked')],
        ];

        $timezones = array_reduce(
            JsonData::timezones(),
            function ($result, $item) {
                $result[$item] = $item;

                return $result;
            }
        );

        $datetimeFormats = array_reduce(
            JsonData::datetimeFormats(),
            function ($result, $item) {
                $result[$item['format']] = $item['title'];

                return $result;
            }
        );

        // Get global and valid 2FA preferences for the user
        $enabled2FA = config('password-policies.2fa_enabled', false);
        $global2FAEnabled = config('password-policies.2fa_method', []);
        $user->preferences_2fa = $user->getValid2FAPreferences();
        $is2FAEnabledForGroup = $user->in2FAGroupOrIndependent();

        $addons = $this->getPluginAddons('edit', compact(['user']));
        $addonsSettings = $this->getPluginAddons('edit.settings', compact(['user']));

        return view('admin.users.edit', compact(
            'user',
            'groups',
            'all_permissions',
            'permissionNames',
            'permissionGroups',
            'states',
            'timezones',
            'countries',
            'datetimeFormats',
            'status',
            'enabled2FA',
            'global2FAEnabled',
            'is2FAEnabledForGroup',
            'addons',
            'addonsSettings',
        ));
    }

    /**
     * Get all groups actives.
     *
     * @return mixed
     */
    private function getAllGroups()
    {
        return Group::where('status', 'ACTIVE')->get();
    }
}
