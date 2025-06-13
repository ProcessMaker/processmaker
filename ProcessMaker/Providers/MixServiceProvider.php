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

                    // Try to get manifest from cache first
                    $manifest = Cache::get('mix-manifest');

                    // If not in cache, read from file and cache it
                    if (is_null($manifest)) {
                        $manifestPath = public_path('mix-manifest.json');
                        if (!file_exists($manifestPath)) {
                            return parent::__invoke($path, $manifestDirectory);
                        }

                        $manifest = json_decode(file_get_contents($manifestPath), true);
                        if ($manifest !== null) {
                            Cache::put('mix-manifest', $manifest, now()->addHours(24));
                        }
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
