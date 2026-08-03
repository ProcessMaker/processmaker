<?php

namespace ProcessMaker\Managers;

class ControllerAddonsRegistry
{
    private $addons = [];

    /**
     * Register a controller addon.
     */
    public function register(string $scope, array $config): void
    {
        $config['scope'] = $scope;
        $this->addons[] = $config;
    }

    /**
     * Get configured addons for a controller.
     */
    public function getAddons(string $scope, string $method, array $data): array
    {
        $addons = [];

        foreach ($this->addons as $addon) {
            if ($addon['method'] !== $method || $addon['scope'] !== $scope) {
                continue;
            }

            if (isset($addon['data']) && is_callable($addon['data'])) {
                $data = call_user_func($addon['data'], $data);
            }

            $addon['content'] = isset($addon['view']) && !isset($addon['content'])
                ? view($addon['view'], $data)->render()
                : (isset($addon['content']) ? $addon['content'] : '');
            $addon['script'] = isset($addon['script']) && is_string($addon['script'])
                ? view($addon['script'], $data)->render()
                : '';
            $addons[] = $addon;
        }

        return $addons;
    }
}
