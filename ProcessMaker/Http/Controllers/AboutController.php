<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Models\Setting;

class AboutController extends Controller
{
    /**
     * Get the list of users.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View
     */
    public function index()
    {
        $root = base_path('');
        $vendor_path = base_path('vendor/processmaker');
        // version from composer
        $composer_json_path = json_decode(file_get_contents($root . '/composer.json'));
        $version = 'ProcessMaker 4 v' . $composer_json_path->version ?? '4.0.0';
        $package_json_path = json_decode(file_get_contents($root . '/package.json'));
        $dependencies = $package_json_path->dependencies;
        $vendor_directories = \File::directories($vendor_path);
        $string = '@processmaker';

        $setting = Setting::byKey('indexed-search');
        if ($setting && $setting->config['enabled'] === true) {
            $indexedSearch = true;
        } else {
            $indexedSearch = false;
        }
        
        $packages = array();

        foreach($vendor_directories as $directory) {
            $content = json_decode(file_get_contents($vendor_path . '/' . basename($directory) . '/composer.json'));
            array_push($packages, $content);
        }

        foreach($dependencies as $key => $value) {
            if (strpos($key, $string) !== false) {
                $value = str_replace('^', '', $value);
                $content = new \stdClass();
                $content->name = $key;
                $content->version = $value;
                array_push($packages, $content);
            }
        }
        
        return view('about.index', compact('packages', 'indexedSearch', 'version'));
    }
}
