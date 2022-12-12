<?php

namespace ProcessMaker\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Log;
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

        $packages = [];

        foreach ($vendor_directories as $directory) {
            $content = json_decode(file_get_contents($vendor_path . '/' . basename($directory) . '/composer.json'));
            array_push($packages, $content);
        }

        foreach ($dependencies as $key => $value) {
            if (strpos($key, $string) !== false) {
                $value = str_replace('^', '', $value);
                $content = new \stdClass();
                $content->name = $key;
                $content->version = $value;
                array_push($packages, $content);
            }
        }

        $commit_hash = false;

        try {
            if (is_string($composer_json_path->extra->processmaker->build)) {
                $commit_hash = $composer_json_path->extra->processmaker->build;
            }
        } catch (Exception $exception) {
            Log::warning('Commit hash missing from composer.json', [
                'composer.json' => $composer_json_path,
            ]);
        }

        return view('about.index', compact('packages', 'indexedSearch', 'version', 'commit_hash'));
    }
}
