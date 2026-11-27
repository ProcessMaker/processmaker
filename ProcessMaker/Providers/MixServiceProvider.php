<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Mix::class, function ($app) {
            return new class extends Mix {
                /**
                 * Get the path to a versioned Mix file.
                 *
                 * @param  string  $path
                 * @param  string  $manifestDirectory
                 * @return \Illuminate\Support\HtmlString|string
                 *
                 * @throws \Exception
                 */
                public function __invoke($path, $manifestDirectory = '')
                {
                    $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

                    if (!$tenant) {
                        return parent::__invoke($path, $manifestDirectory);
                    }

                    $manifestPath = public_path('mix-manifest.json');
                    $manifest = null;
                    if (file_exists($manifestPath)) {
                        $manifest = json_decode(file_get_contents($manifestPath), true);
                    }

                    if ($manifest === null) {
                        $manifest = Cache::get('mix-manifest');
                    }

                    if ($manifest === null) {
                        return parent::__invoke($path, $manifestDirectory);
                    }

                    $keys = array_keys($manifest);

                    $pathInfo = pathinfo($path);
                    $tenantPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_tenant_' . $tenant->id;

                    $matchingKeys = array_values(array_filter($keys, function ($key) use ($tenantPath) {
                        return Str::contains($key, $tenantPath);
                    }));

                    if (count($matchingKeys) === 1) {
                        $path = $matchingKeys[0];
                        // \Log::info('MixServiceProvider: ' . $path);
                    }

                    return parent::__invoke($path, $manifestDirectory);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
