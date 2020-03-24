<?php

namespace ProcessMaker\Http\Controllers;

use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;

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
        $package_json_path = json_decode(file_get_contents($root . '/package.json'));
        $dependencies = $package_json_path->dependencies;
        $vendor_directories = \File::directories($vendor_path);
        $string = '@processmaker';
        
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
        
        return view('about.index', compact('packages'));
    }
}
