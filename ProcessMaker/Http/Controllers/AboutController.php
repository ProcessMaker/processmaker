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
        $path = base_path('vendor/processmaker');
        $directories = \File::directories($path);
        $packages = array();
        foreach($directories as $directory) {
            $content = json_decode(file_get_contents($path . '/' . basename($directory) . '/composer.json'));
            array_push($packages, $content);
        }
        return view('about.index', compact('packages'));
    }
}
