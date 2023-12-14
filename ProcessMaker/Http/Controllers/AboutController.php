<?php

namespace ProcessMaker\Http\Controllers;

use Exception;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Facades\MessageBrokerService;
use ProcessMaker\Models\Setting;
use Throwable;

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
        $versionTitle = $composer_json_path->extra->processmaker->release ?? '';
        $versionNumber = $composer_json_path->version ?? '4.0.0';
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

        $microServices = [];

        $aiMicroService = $this->getAiMicroService();
        if ($aiMicroService) {
            $microServices = [$aiMicroService];
        }

        $nayraMicroService = $this->getNayraMicroServiceAbout();
        if ($nayraMicroService) {
            $microServices[] = $nayraMicroService;
        }

        $installed = app(PackageManifest::class)->list();
        $packages = array_filter($packages, function ($package) use ($installed) {
            return in_array($package->name, $installed);
        });

        $view = request()->get('partial') === 'ms' ? 'about.microservices' : 'about.index';

        return view($view,
            compact(
                'packages',
                'indexedSearch',
                'versionTitle',
                'versionNumber',
                'commit_hash',
                'microServices'
            )
        );
    }

    private function getAiMicroService()
    {
        if (hasPackage('package-ai')) {
            $url = config('app.ai_microservice_host') . '/pm/getVersion';
            try {
                $response = Http::post($url, []);
            } catch (Throwable $th) {
                return [
                    'name' => 'Pmai microservice',
                    'waiting' => true,
                ];
            }

            return $response->json();
        }

        return null;
    }

    /**
     * Get the Nayra microservice about information from cache or send about message to receive it.
     *
     * @return array|null
     */
    private function getNayraMicroServiceAbout(): ?array
    {
        if (config('app.message_broker_driver') !== 'default') {
            $about = Cache::get('nayra.about', null);
            if (!$about) {
                // Send about message to receive about information from nayra service
                try {
                    MessageBrokerService::sendAboutMessage();
                } catch (Throwable $e) {
                    return [
                        'name' => 'processmaker/nayra-service',
                        'description' => __('Nayra microservice is not available at this moment.'),
                        'waiting' => true,
                    ];
                }
                $about = [
                    'name' => 'processmaker/nayra-service',
                    'waiting' => true,
                ];
            }

            return $about;
        }

        return null;
    }
}
