<?php

namespace ProcessMaker\Http\Controllers\Admin;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Get the list of settings.
     *
     * @return Factory|View
     */
    public function index()
    {
        if (Setting::notHidden()->count()) {
            return view('admin.settings.index');
        } else {
            abort(404);
        }
    }

    /**
     * Download an exported JSON file of settings.
     *
     * @return Factory|View
     */
    public function export(Request $request)
    {
        $query = Setting::query();

        $group = $request->input('group');
        if (!empty($group)) {
            if ($group === 'System') {
                $fileName = 'System.json';
                $query->whereNull('group');
            } else {
                $fileName = "{$group}.json";
                $query->where('group', $group);
            }
        } else {
            $fileName = 'Settings.json';
        }

        $settings = $query->get()->toArray();

        $file = [
            'type' => 'settings_package',
            'version' => '1',
            'settings' => array_map(function($setting) {
                return [
                    'key' => $setting['key'],
                    'config' => $setting['config'],
                ];
            }, $settings),
        ];

        return response()->streamDownload(function () use ($file) {
            echo json_encode($file);
        }, $fileName, [
            'Content-type' => 'application/json',
        ]);
    }
}
