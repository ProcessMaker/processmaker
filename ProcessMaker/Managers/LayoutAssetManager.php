<?php

namespace ProcessMaker\Managers;

use Illuminate\Http\Request;
use InvalidArgumentException;

class LayoutAssetManager
{
    /**
     * Resolve the asset profile for the given request.
     */
    public function forRequest(?Request $request = null): array
    {
        $request = $request ?? request();
        $profileName = $this->resolveProfileName($request);
        $profiles = config('layout-assets.profiles', []);
        $defaultProfile = $profiles['default'] ?? null;

        if ($defaultProfile === null) {
            throw new InvalidArgumentException('layout-assets.profiles.default is not configured.');
        }

        if ($profileName === 'default' || !isset($profiles[$profileName])) {
            return array_merge($defaultProfile, ['profile' => 'default']);
        }

        return array_merge($defaultProfile, $profiles[$profileName], ['profile' => $profileName]);
    }

    /**
     * Determine whether a boolean asset flag is enabled for the request.
     */
    public function requires(string $asset, ?Request $request = null): bool
    {
        $profile = $this->forRequest($request);

        if (!array_key_exists($asset, $profile)) {
            throw new InvalidArgumentException("Unknown layout asset flag: {$asset}");
        }

        return (bool) $profile[$asset];
    }

    /**
     * Resolve profile name from route patterns in config.
     */
    public function resolveProfileName(Request $request): string
    {
        foreach (config('layout-assets.routes', []) as $profileName => $patterns) {
            if ($request->is(...$patterns)) {
                return $profileName;
            }
        }

        return 'default';
    }
}
