<?php

namespace ProcessMaker\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class MediaUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $url = parent::getUrl();

        // Get the current tenant ID
        $tenant = app('currentTenant');
        if ($tenant) {
            // Get the base URL from tenant config or use the current URL
            $baseUrl = $tenant->config['app.url'] ?? config('app.url');

            // Extract the path from the URL
            $path = parse_url($url, PHP_URL_PATH);

            // Replace the storage path with tenant-specific path
            $path = str_replace('/storage/', '/storage/tenant_' . $tenant->id . '/', $path);

            // Reconstruct the URL with the new path
            $url = rtrim($baseUrl, '/') . $path;
        }

        return $url;
    }
}
