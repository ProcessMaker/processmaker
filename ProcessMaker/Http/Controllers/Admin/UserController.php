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

        $langs = [];
        foreach (scandir(resource_path('lang')) as $file) {
            preg_match('/([a-z]{2})\\.json/', $file, $matches);
            if (!empty($matches)) {
                $langs[] = $matches[1];
            }
        }
        // Our form controls need attribute:value pairs sot we convert the langs array to and associative one
        $availableLangs = array_combine($langs, $langs);

        $currentUser = $user;
        $states = JsonData::states();
        $countries = JsonData::countries();
        $status = [
            ['value' => 'ACTIVE', 'text' => __('Active')],
            ['value' => 'INACTIVE', 'text' => __('Inactive')],
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
            'availableLangs',
            'status',
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
