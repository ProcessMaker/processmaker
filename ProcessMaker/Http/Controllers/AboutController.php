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
        $vendor_path = base_path('vendor/processmaker');
        $node_path = base_path('node_modules/@processmaker');
        
        $vendor_directories = \File::directories($vendor_path);
        $node_directories = \File::directories($node_path);
        
        $packages = array();

        foreach($vendor_directories as $directory) {
            $content = json_decode(file_get_contents($vendor_path . '/' . basename($directory) . '/composer.json'));
            array_push($packages, $content);
        }
        foreach($node_directories as $directory) {
            $content = json_decode(file_get_contents($node_path . '/' . basename($directory) . '/package.json'));
            array_push($packages, $content);
        }
        return view('about.index', compact('packages'));
    }
}
